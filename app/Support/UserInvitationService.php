<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserInvitationService
{
    public static function prepare(User $user, ?string $temporaryPassword = null): array
    {
        $temporaryPassword = $temporaryPassword ?: self::temporaryPassword();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
            'status' => 'active',
        ])->save();

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'temporary_password' => $temporaryPassword,
            'login_url' => self::loginUrl($user),
            'message' => self::message($user, $temporaryPassword),
        ];
    }

    public static function message(User $user, string $temporaryPassword): string
    {
        $roleLabel = ucwords(str_replace('_', ' ', $user->role));
        $organizationName = $user->organization?->name ?? 'your organization';

        return implode("\n", [
            'Hello ' . $user->name . ',',
            '',
            'Your OpsBridge account for ' . $organizationName . ' is ready.',
            '',
            'Login URL: ' . self::loginUrl($user),
            'Email: ' . $user->email,
            'Temporary Password: ' . $temporaryPassword,
            'Access Type: ' . $roleLabel,
            '',
            'First steps after login:',
            '1. Change your password.',
            '2. Review your dashboard and assigned work.',
            '3. Contact your administrator if anything looks incorrect.',
            '',
            'Please confirm once you are able to sign in.',
        ]);
    }

    public static function status(User $user): array
    {
        if ($user->invitation_accepted_at || ($user->last_login_at && ! $user->must_change_password)) {
            return ['label' => 'Accepted', 'badge' => 'success'];
        }

        if ($user->invitation_sent_at) {
            return ['label' => 'Pending', 'badge' => 'warning text-dark'];
        }

        return ['label' => 'Not Invited', 'badge' => 'secondary'];
    }

    private static function loginUrl(User $user): string
    {
        $domain = $user->isSuperAdmin() || $user->isPartner()
            ? config('niyantron.platform_domain')
            : config('niyantron.products.opsbridge.domain');

        $domain = trim(str_replace(['https://', 'http://'], '', (string) $domain), '/');

        if (! $domain || app()->environment('local', 'testing')) {
            return route('login');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme . '://' . $domain . '/login';
    }

    private static function temporaryPassword(): string
    {
        return 'Ops@' . Str::upper(Str::random(4)) . random_int(1000, 9999);
    }
}
