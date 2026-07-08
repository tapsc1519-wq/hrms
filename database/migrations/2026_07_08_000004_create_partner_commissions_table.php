<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.platform_connection', 'platform');

        Schema::connection($connection)->create('partner_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('organization_payment_id')->nullable();
            $table->unsignedBigInteger('organization_product_subscription_id')->nullable();
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('organization_payment_id', 'partner_commissions_payment_unique');
            $table->index(['partner_id', 'status']);
            $table->index(['product_id', 'payment_date']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->dropIfExists('partner_commissions');
    }
};
