<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.platform_connection', 'platform');

        if (Schema::connection($connection)->hasTable('organization_product_subscriptions')) {
            return;
        }

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
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->dropIfExists('organization_product_subscriptions');
    }
};
