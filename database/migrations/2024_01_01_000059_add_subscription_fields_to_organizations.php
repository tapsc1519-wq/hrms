<?php

use App\Support\ModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'trial_months')) {
                $table->unsignedTinyInteger('trial_months')->default(1)->after('status');
            }
            if (!Schema::hasColumn('organizations', 'trial_started_at')) {
                $table->timestamp('trial_started_at')->nullable()->after('trial_months');
            }
            if (!Schema::hasColumn('organizations', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('trial_started_at');
            }
            if (!Schema::hasColumn('organizations', 'billing_status')) {
                $table->enum('billing_status', ['trial', 'active', 'overdue', 'suspended', 'cancelled'])->default('trial')->after('trial_ends_at');
            }
            if (!Schema::hasColumn('organizations', 'billing_cycle')) {
                $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly')->after('billing_status');
            }
            if (!Schema::hasColumn('organizations', 'monthly_amount')) {
                $table->decimal('monthly_amount', 12, 2)->default(0)->after('billing_cycle');
            }
        });

        Schema::table('organization_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('organization_modules', 'monthly_price')) {
                $table->decimal('monthly_price', 12, 2)->default(0)->after('updated_by');
            }
        });

        $now = now();
        foreach (ModuleRegistry::all() as $key => $module) {
            DB::table('organization_modules')
                ->where('module_key', $key)
                ->update(['monthly_price' => $module['monthly_price'] ?? 0]);
        }

        DB::table('organizations')->orderBy('id')->get(['id'])->each(function ($org) use ($now) {
            $startedAt = $now->copy();
            $monthlyAmount = DB::table('organization_modules')
                ->where('organization_id', $org->id)
                ->where('is_enabled', true)
                ->sum('monthly_price');

            DB::table('organizations')
                ->where('id', $org->id)
                ->update([
                    'trial_months' => 1,
                    'trial_started_at' => $startedAt,
                    'trial_ends_at' => $startedAt->copy()->addMonth()->toDateString(),
                    'billing_status' => 'trial',
                    'billing_cycle' => 'monthly',
                    'monthly_amount' => $monthlyAmount,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('organization_modules', function (Blueprint $table) {
            if (Schema::hasColumn('organization_modules', 'monthly_price')) {
                $table->dropColumn('monthly_price');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            foreach (['monthly_amount', 'billing_cycle', 'billing_status', 'trial_ends_at', 'trial_started_at', 'trial_months'] as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
