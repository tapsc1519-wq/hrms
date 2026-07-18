<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetRepair;
use App\Models\AssetRequest;
use App\Models\DeviceAgent;
use App\Models\EmployeeProfile;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\OrganizationRole;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\Product;
use App\Models\Software;
use App\Models\SoftwareLicense;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ProductionReadinessService
{
    public static function forOrganization(User $user): array
    {
        $organization = $user->organization?->load('modules');
        $orgId = (int) $user->organization_id;
        $moduleEnabled = fn (string $module): bool => !$organization || $organization->hasModule($module);

        $checks = [
            'Foundation' => [
                self::check(! config('app.debug'), 'Production debug is disabled', 'APP_DEBUG is still enabled. Disable it on the live server.', 'fail'),
                self::check(str_starts_with((string) config('app.url'), 'https://'), 'Application URL uses HTTPS', 'APP_URL should use https on production.', 'warn'),
                self::check(is_link(public_path('storage')) || file_exists(public_path('storage')), 'Public storage link exists', 'Run php artisan storage:link so uploaded files and agent packages are available.', 'fail'),
                self::check(self::mailReady(), 'Email delivery is configured', 'SMTP still looks incomplete. Ask Super Admin to configure email before inviting real customers.', 'warn'),
            ],
            'Organization Setup' => [
                self::check((bool) $organization, 'Organization record exists', 'This account is not linked to an organization.', 'fail'),
                self::check(EmployeeProfile::where('organization_id', $orgId)->exists(), 'Employees are configured', 'Add employees before testing attendance, assets, software and tasks.', 'warn', route('admin.employees.index')),
                self::check(OrganizationRole::where('organization_id', $orgId)->exists(), 'Permission roles are available', 'Create clear permission roles for HR, IT, procurement and support teams.', 'warn', route('admin.roles.index')),
                self::check(Task::where('organization_id', $orgId)->exists(), 'Work management is in use', 'Create setup or QA tasks and assign owners before launch.', 'warn', route('admin.tasks.index')),
            ],
        ];

        if ($moduleEnabled('itam')) {
            $checks['ITAM'] = [
                self::check(Asset::where('organization_id', $orgId)->exists(), 'Assets are loaded', 'Add or import assets before going live.', 'warn', route('admin.assets.index')),
                self::check(AssetRequest::where('organization_id', $orgId)->where('status', 'pending')->count() === 0, 'No pending asset requests', 'Review pending employee asset requests.', 'warn', route('admin.requests.index', ['status' => 'pending'])),
                self::check(AssetRepair::where('organization_id', $orgId)->where('status', 'qc_pending')->count() === 0, 'No repair QC backlog', 'Complete pending quality checks before closing launch QA.', 'warn', route('admin.asset-repairs.index', ['status' => 'qc_pending'])),
            ];
        }

        if ($moduleEnabled('sam')) {
            $unlinkedEndpoints = DeviceAgent::where('organization_id', $orgId)
                ->where(fn ($query) => $query->whereNull('asset_id')->orWhereNull('user_id'))
                ->count();

            $checks['SAM & Endpoints'] = [
                self::check(Software::where('organization_id', $orgId)->exists(), 'Software catalog exists', 'Add software titles before compliance tracking.', 'warn', route('admin.software.index')),
                self::check(SoftwareLicense::where('organization_id', $orgId)->exists(), 'Software licenses exist', 'Add licenses for audit and compliance coverage.', 'warn', route('admin.software-licenses.index')),
                self::check(DeviceAgent::where('organization_id', $orgId)->exists(), 'At least one endpoint enrolled', 'Install an agent on a test device and confirm check-in.', 'warn', route('admin.agent-sources.index')),
                self::check($unlinkedEndpoints === 0, 'Endpoints are linked', "{$unlinkedEndpoints} endpoint(s) need asset or employee links.", 'warn', route('admin.agent-sources.index', ['linking' => 'asset_missing'])),
            ];
        }

        if ($moduleEnabled('support')) {
            $checks['Support'] = [
                self::check(Ticket::where('organization_id', $orgId)->whereIn('status', ['open', 'in_progress'])->count() === 0, 'No active support backlog', 'Review open support tickets before launch handover.', 'warn', route('admin.tickets.index')),
            ];
        }

        $checks['Launch Blockers'] = [
            self::check(Task::where('organization_id', $orgId)->open()->whereNotNull('due_at')->where('due_at', '<', now())->count() === 0, 'No overdue tasks', 'Close, reschedule, or update overdue tasks.', 'warn', route('admin.tasks.index', ['due' => 'overdue'])),
            self::check(Task::where('organization_id', $orgId)->where('status', 'blocked')->count() === 0, 'No blocked tasks', 'Resolve blocked work before production sign-off.', 'warn', route('admin.tasks.index', ['status' => 'blocked'])),
        ];

        return self::summary($checks);
    }

    public static function forPlatform(): array
    {
        $checks = [
            'Platform Foundation' => [
                self::check(! config('app.debug'), 'Production debug is disabled', 'APP_DEBUG should be false on platform production.', 'fail'),
                self::check(str_starts_with((string) config('app.url'), 'https://'), 'Platform URL uses HTTPS', 'APP_URL should use https for cookies, sessions and redirects.', 'warn'),
                self::check(self::platformTablesReady(), 'Platform database tables exist', 'Run platform migrations before managing products and subscriptions.', 'fail'),
                self::check(self::mailReady(), 'Platform email is configured', 'Configure SMTP before invitations and partner communication.', 'warn', route('super-admin.mail-settings.index')),
            ],
            'Products & Subscriptions' => [
                self::check(Product::where('status', 'active')->exists(), 'At least one active product', 'Activate OpsBridge and future products from product settings.', 'fail', route('super-admin.products.index')),
                self::check(Organization::exists(), 'Organizations exist', 'Onboard at least one organization before launch.', 'warn', route('super-admin.organizations.index')),
                self::check(OrganizationProductSubscription::exists(), 'Product subscriptions exist', 'Map organizations to purchased products.', 'warn', route('super-admin.product-subscriptions.index')),
                self::check(OrganizationProductSubscription::whereIn('status', ['overdue', 'suspended'])->count() === 0, 'No subscription access blockers', 'Resolve overdue or suspended subscriptions.', 'warn', route('super-admin.product-subscriptions.index')),
            ],
            'Partner Channel' => [
                self::check(Partner::exists(), 'Partner module has records', 'Add partners when your channel program starts.', 'warn', route('super-admin.partners.index')),
                self::check(PartnerCommission::where('status', 'pending')->count() === 0, 'No pending commission payout', 'Approve or process pending partner commissions.', 'warn', route('super-admin.partner-commissions.index', ['status' => 'pending'])),
            ],
        ];

        return self::summary($checks);
    }

    private static function check(bool $passed, string $title, string $message, string $severity = 'warn', ?string $url = null): array
    {
        return [
            'title' => $title,
            'message' => $message,
            'status' => $passed ? 'pass' : $severity,
            'url' => $passed ? null : $url,
        ];
    }

    private static function summary(array $groups): array
    {
        $flat = collect($groups)->flatten(1);
        $totals = [
            'total' => $flat->count(),
            'pass' => $flat->where('status', 'pass')->count(),
            'warn' => $flat->where('status', 'warn')->count(),
            'fail' => $flat->where('status', 'fail')->count(),
        ];

        return [
            'groups' => $groups,
            'totals' => $totals,
            'score' => $totals['total'] > 0 ? (int) round(($totals['pass'] / $totals['total']) * 100) : 100,
        ];
    }

    private static function mailReady(): bool
    {
        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $from = (string) config('mail.from.address');

        return $mailer === 'smtp'
            && filled($host)
            && ! in_array($host, ['mailpit', 'localhost'], true)
            && filled($from)
            && ! str_contains($from, 'example.com');
    }

    private static function platformTablesReady(): bool
    {
        try {
            return Schema::connection(config('database.platform_connection', 'platform'))->hasTable('products')
                && Schema::connection(config('database.platform_connection', 'platform'))->hasTable('organization_product_subscriptions')
                && Schema::connection(config('database.platform_connection', 'platform'))->hasTable('partners');
        } catch (\Throwable) {
            return false;
        }
    }
}
