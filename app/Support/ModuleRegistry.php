<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class ModuleRegistry
{
    public static function all(): array
    {
        return [
            'itam' => [
                'product_key' => 'opsbridge',
                'name' => 'IT Asset Management',
                'short_name' => 'ITAM',
                'description' => 'Hardware assets, suppliers, purchases, assignments, maintenance, requests, catalog and asset reports.',
                'icon' => 'bi-box-seam-fill',
                'color' => 'blue',
                'monthly_price' => 4999,
                'depends_on' => [],
            ],
            'sam' => [
                'product_key' => 'opsbridge',
                'name' => 'Software Asset Management',
                'short_name' => 'SAM',
                'description' => 'Software catalog, license inventory, software assignment and compliance tracking.',
                'icon' => 'bi-display-fill',
                'color' => 'purple',
                'monthly_price' => 2999,
                'depends_on' => [],
            ],
            'hrms' => [
                'product_key' => 'opsbridge',
                'name' => 'Human Resource Management',
                'short_name' => 'HRMS',
                'description' => 'Employees, profiles, documents, shifts, attendance, leaves, holidays and HR settings.',
                'icon' => 'bi-person-vcard-fill',
                'color' => 'green',
                'monthly_price' => 5999,
                'depends_on' => [],
            ],
            'payroll' => [
                'product_key' => 'opsbridge',
                'name' => 'Payroll',
                'short_name' => 'Payroll',
                'description' => 'Salary setup, payroll runs, payslips, approvals and bank payment exports.',
                'icon' => 'bi-cash-stack',
                'color' => 'amber',
                'monthly_price' => 3999,
                'depends_on' => ['hrms'],
            ],
            'support' => [
                'product_key' => 'opsbridge',
                'name' => 'Support Tickets',
                'short_name' => 'Support',
                'description' => 'Employee/admin ticket bridge with comments and supporting attachments.',
                'icon' => 'bi-headset',
                'color' => 'teal',
                'monthly_price' => 1499,
                'depends_on' => [],
            ],
            'supplier_portal' => [
                'product_key' => 'opsbridge',
                'name' => 'Supplier Portal',
                'short_name' => 'Supplier Portal',
                'description' => 'Supplier login area for purchase order visibility and supplier collaboration.',
                'icon' => 'bi-building-fill',
                'color' => 'slate',
                'monthly_price' => 999,
                'depends_on' => ['itam'],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(static::all());
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    public static function forProduct(string $productKey): array
    {
        return array_filter(
            static::all(),
            fn (array $module) => ($module['product_key'] ?? 'opsbridge') === $productKey
        );
    }

    public static function monthlyPrice(string $key): float
    {
        $default = (float) (static::get($key)['monthly_price'] ?? 0);

        if (!static::settingsTableExists()) {
            return $default;
        }

        return (float) Setting::get("module_price_{$key}", $default);
    }

    public static function formatInr(float|int|null $amount): string
    {
        return "\u{20B9}" . number_format((float) $amount, 2);
    }

    private static function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function dependenciesFor(array $enabledKeys): array
    {
        $enabled = array_values(array_unique($enabledKeys));

        foreach ($enabled as $key) {
            foreach (static::get($key)['depends_on'] ?? [] as $dependency) {
                if (!in_array($dependency, $enabled, true)) {
                    $enabled[] = $dependency;
                }
            }
        }

        return $enabled;
    }
}
