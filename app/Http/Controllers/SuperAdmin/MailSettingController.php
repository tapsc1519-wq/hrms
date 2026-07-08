<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailSettingController extends Controller
{
    public function index()
    {
        $defaultMailer = config('mail.default');
        $smtp = config('mail.mailers.smtp', []);

        $mail = [
            'default_mailer' => $defaultMailer,
            'active_transport' => config("mail.mailers.{$defaultMailer}.transport", $defaultMailer),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'smtp_host' => $smtp['host'] ?? null,
            'smtp_port' => $smtp['port'] ?? null,
            'smtp_scheme' => $smtp['scheme'] ?? null,
            'smtp_username' => $this->maskValue($smtp['username'] ?? null),
            'smtp_password_set' => filled($smtp['password'] ?? null),
            'platform_login_url' => 'https://' . rtrim((string) config('niyantron.platform_domain'), '/') . '/login',
        ];

        return view('super-admin.mail-settings.index', compact('mail'));
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::raw($this->testMessageBody(), function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('Niyantron Platform test email');
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Mail test failed: ' . $exception->getMessage());
        }

        return back()->with('success', 'Test email sent to ' . $validated['email'] . '.');
    }

    private function testMessageBody(): string
    {
        return implode(PHP_EOL, [
            'This is a test email from Niyantron Platform.',
            '',
            'If you received this message, outgoing platform mail is working.',
            'Platform Login: https://' . rtrim((string) config('niyantron.platform_domain'), '/') . '/login',
            'Sent At: ' . now()->format('d M Y, h:i A'),
        ]);
    }

    private function maskValue(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 2) . str_repeat('*', max(strlen($value) - 4, 4)) . substr($value, -2);
    }
}
