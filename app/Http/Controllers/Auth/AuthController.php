<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\OrganizationSsoSetting;
use App\Models\Product;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('auth.login', [
            'ssoProviders' => [
                'google' => $this->hasConfiguredOrganizationSso('google'),
                'microsoft' => $this->hasConfiguredOrganizationSso('microsoft'),
            ],
        ]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->isAdmin() && ! $user->last_login_at) {
                $request->session()->put('admin_first_login', true);
            }
            $user->update(['last_login_at' => now()]);
            return $this->redirectByRole($user->role);
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function redirectToSso(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:google,microsoft'],
            'organization' => ['required', 'string', 'max:255'],
        ]);

        $provider = $validated['provider'];

        if (!$this->isSupportedSsoProvider($provider)) {
            abort(404);
        }

        $organization = $this->resolveOrganizationForSso($validated['organization']);

        if (!$organization) {
            return redirect()->route('login')
                ->with('error', 'We could not find that organization. Try the organization name, workspace slug, admin email, or company email domain.')
                ->withInput();
        }

        $setting = $organization->ssoSettings()
            ->where('provider', $provider)
            ->first();

        if (!$setting?->isReady()) {
            return redirect()->route('login')
                ->with('error', ucfirst($provider) . ' SSO is not enabled for ' . $organization->name . '.')
                ->withInput();
        }

        $request->session()->put('sso', [
            'organization_id' => $organization->id,
            'provider' => $provider,
        ]);

        return $this->socialiteDriver($setting)->redirect();
    }

    public function handleSsoCallback(Request $request)
    {
        $ssoSession = $request->session()->pull('sso');
        $provider = $ssoSession['provider'] ?? null;
        $organizationId = $ssoSession['organization_id'] ?? null;

        if (!$this->isSupportedSsoProvider($provider)) {
            return redirect()->route('login')
                ->with('error', 'Your SSO session expired. Please start Microsoft or Google sign-in again.');
        }

        $setting = OrganizationSsoSetting::where('organization_id', $organizationId)
            ->where('provider', $provider)
            ->with('organization')
            ->first();

        if (!$setting?->isReady()) {
            return redirect()->route('login')
                ->with('error', ucfirst($provider) . ' SSO is no longer enabled for this organization.');
        }

        try {
            $ssoUser = $this->socialiteDriver($setting)->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->with('error', 'Unable to sign in with ' . ucfirst($provider) . '. Please try again.');
        }

        $email = strtolower((string) ($ssoUser->getEmail() ?: ($ssoUser->user['mail'] ?? $ssoUser->user['userPrincipalName'] ?? '')));

        if ($email === '') {
            return redirect()->route('login')
                ->with('error', ucfirst($provider) . ' did not return an email address for this account.');
        }

        if (!$setting->allowsEmail($email)) {
            return redirect()->route('login')
                ->with('error', $email . ' is not allowed for ' . $setting->organization->name . ' SSO.');
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('organization_id', $setting->organization_id)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'No active user account exists for ' . $email . ' in ' . $setting->organization->name . '. Please ask your administrator to create your account first.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        if ($user->isAdmin() && ! $user->last_login_at) {
            $request->session()->put('admin_first_login', true);
        }
        $user->update(['last_login_at' => now()]);

        return $this->redirectByRole($user->role);
    }
    public function register(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_email' => ['required', 'email', 'max:255', 'unique:organizations,email', 'unique:users,email'],
            'organization_phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $organization = Organization::create([
                'name' => $validated['organization_name'],
                'slug' => $this->uniqueOrganizationSlug($validated['organization_name']),
                'email' => $validated['organization_email'],
                'phone' => $validated['organization_phone'] ?? null,
                'status' => 'active',
                'trial_months' => 1,
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addMonth()->toDateString(),
                'billing_status' => 'trial',
                'billing_cycle' => 'monthly',
            ]);

            $organization->syncModules(ModuleRegistry::keys());
            $this->syncOpsBridgeSubscription($organization);

            return User::create([
                'organization_id' => $organization->id,
                'name' => $validated['organization_name'] . ' Admin',
                'email' => $validated['organization_email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'status' => 'active',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        session()->put('admin_first_login', true);

        return redirect()->route('admin.welcome.index')
            ->with('success', 'Your organization account is ready. Your one-month free trial has started.');
    }

    public function editPassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'invitation_accepted_at' => $request->user()->invitation_accepted_at ?: now(),
        ])->save();

        if ($request->user()->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('success', 'Your password has been changed successfully.');
        }

        return back()->with('success', 'Your password has been changed successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function isSupportedSsoProvider(?string $provider): bool
    {
        return in_array($provider, ['google', 'microsoft'], true);
    }

    private function ssoProviderConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"))
            && filled(config("services.{$provider}.redirect"));
    }

    private function hasConfiguredOrganizationSso(string $provider): bool
    {
        return OrganizationSsoSetting::where('provider', $provider)
            ->where('is_enabled', true)
            ->whereNotNull('client_id')
            ->whereNotNull('client_secret')
            ->exists();
    }

    private function resolveOrganizationForSso(string $value): ?Organization
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $query = Organization::where('status', 'active')
            ->where(function ($query) use ($value) {
                $query->whereRaw('LOWER(slug) = ?', [$value])
                    ->orWhereRaw('LOWER(name) = ?', [$value])
                    ->orWhereRaw('LOWER(email) = ?', [$value]);

                if (str_contains($value, '@')) {
                    $domain = str($value)->afterLast('@')->toString();
                    $query->orWhereRaw('LOWER(email) LIKE ?', ['%@' . $domain]);
                } elseif (str_contains($value, '.')) {
                    $query->orWhereRaw('LOWER(email) LIKE ?', ['%@' . $value]);
                }
            });

        return $query->first();
    }

    private function socialiteDriver(OrganizationSsoSetting $setting)
    {
        Config::set("services.{$setting->provider}", [
            'client_id' => $setting->client_id,
            'client_secret' => $setting->client_secret,
            'redirect' => route('sso.callback'),
            'tenant' => $setting->provider === 'microsoft' ? ($setting->tenant ?: 'common') : null,
            'include_tenant_info' => $setting->provider === 'microsoft',
        ]);

        Socialite::forgetDrivers();

        return Socialite::driver($setting->provider);
    }
    private function redirectByRole(string $role)
    {
        if ($role === 'admin' && Auth::user()?->isAdmin() && (session('admin_first_login') || Auth::user()?->must_change_password)) {
            return $this->redirectToPortalRoute('admin.welcome.index', config('niyantron.products.opsbridge.domain'));
        }

        return match($role) {
            'super_admin' => $this->redirectToPortalRoute('super-admin.dashboard', config('niyantron.platform_domain')),
            'admin'       => $this->redirectToPortalRoute('admin.dashboard', config('niyantron.products.opsbridge.domain')),
            'supplier'    => $this->redirectToPortalRoute('supplier.dashboard', config('niyantron.products.opsbridge.domain')),
            'partner'     => $this->redirectToPortalRoute('partner.dashboard', config('niyantron.platform_domain')),
            'staff'       => $this->redirectToPortalRoute('staff.dashboard', config('niyantron.products.opsbridge.domain')),
            default       => redirect()->route('login'),
        };
    }

    private function redirectToPortalRoute(string $routeName, ?string $domain)
    {
        if (!$this->shouldUsePortalDomain($domain)) {
            return redirect()->route($routeName);
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return redirect()->to($scheme . '://' . $domain . route($routeName, [], false));
    }

    private function shouldUsePortalDomain(?string $domain): bool
    {
        if (blank($domain) || app()->environment('local', 'testing')) {
            return false;
        }

        return request()->getHost() !== $domain;
    }

    private function uniqueOrganizationSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $counter = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function syncOpsBridgeSubscription(Organization $organization): void
    {
        $product = Product::where('slug', 'opsbridge')->first();

        if (!$product) {
            return;
        }

        OrganizationProductSubscription::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'product_id' => $product->id,
            ],
            [
                'status' => $organization->billing_status ?: 'trial',
                'plan_name' => 'OpsBridge',
                'billing_cycle' => $organization->billing_cycle ?: 'monthly',
                'monthly_amount' => $organization->monthly_amount ?: collect(ModuleRegistry::keys())
                    ->sum(fn (string $key) => ModuleRegistry::monthlyPrice($key)),
                'trial_started_at' => $organization->trial_started_at,
                'trial_ends_at' => $organization->trial_ends_at,
                'subscription_started_at' => $organization->last_payment_at,
                'subscription_ends_at' => $organization->subscription_ends_at,
                'last_payment_at' => $organization->last_payment_at,
                'product_database' => config('database.connections.' . config('database.product_connection', 'opsbridge') . '.database'),
                'product_domain' => $product->domain ?: 'opsbridge.niyantron.com',
            ]
        );
    }
}
