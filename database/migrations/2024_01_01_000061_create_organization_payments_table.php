<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'subscription_ends_at')) {
                $table->date('subscription_ends_at')->nullable()->after('monthly_amount');
            }
            if (!Schema::hasColumn('organizations', 'last_payment_at')) {
                $table->timestamp('last_payment_at')->nullable()->after('subscription_ends_at');
            }
        });

        Schema::create('organization_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('payment_method', ['bank_transfer', 'upi', 'cheque', 'cash', 'card', 'other'])->default('bank_transfer');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_payments');

        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'last_payment_at')) {
                $table->dropColumn('last_payment_at');
            }
            if (Schema::hasColumn('organizations', 'subscription_ends_at')) {
                $table->dropColumn('subscription_ends_at');
            }
        });
    }
};
