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
        $connection = config('database.platform_connection', 'platform');

        Schema::connection($connection)->create('organization_product_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['trial', 'active', 'overdue', 'suspended', 'cancelled'])->default('trial');
            $table->string('plan_name')->nullable();
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');
            $table->decimal('monthly_amount', 12, 2)->default(0);
            $table->timestamp('trial_started_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->date('subscription_ends_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->string('product_database')->nullable();
            $table->string('product_domain')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'product_id'], 'org_product_subscriptions_unique');
            $table->index(['product_id', 'status'], 'org_product_status_idx');
        });

        $productId = DB::connection($connection)
            ->table('products')
            ->where('slug', 'opsbridge')
            ->value('id');

        if (!$productId) {
            return;
        }

        DB::connection(config('database.default'))
            ->table('organizations')
            ->orderBy('id')
            ->select([
                'id',
                'billing_status',
                'billing_cycle',
                'monthly_amount',
                'trial_started_at',
                'trial_ends_at',
                'subscription_ends_at',
                'last_payment_at',
            ])
            ->chunkById(100, function ($organizations) use ($connection, $productId) {
                $now = now();
                $rows = $organizations->map(function ($organization) use ($productId, $now) {
                    return [
                        'organization_id' => $organization->id,
                        'product_id' => $productId,
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
                        'product_database' => env('DB_OPSBRIDGE_DATABASE', env('DB_DATABASE')),
                        'product_domain' => env('OPSBRIDGE_DOMAIN', 'opsbridge.niyantron.com'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                DB::connection($connection)
                    ->table('organization_product_subscriptions')
                    ->upsert(
                        $rows,
                        ['organization_id', 'product_id'],
                        [
                            'status',
                            'plan_name',
                            'billing_cycle',
                            'monthly_amount',
                            'trial_started_at',
                            'trial_ends_at',
                            'subscription_started_at',
                            'subscription_ends_at',
                            'last_payment_at',
                            'product_database',
                            'product_domain',
                            'updated_at',
                        ]
                    );
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->dropIfExists('organization_product_subscriptions');
    }
};
