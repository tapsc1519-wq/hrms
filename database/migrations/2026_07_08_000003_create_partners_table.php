<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.platform_connection', 'platform');

        Schema::connection($connection)->create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('type', ['individual', 'agency', 'reseller', 'consultant'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->decimal('default_commission_percent', 5, 2)->default(0);
            $table->string('payout_method')->nullable();
            $table->text('payout_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('email');
        });

        Schema::connection($connection)->table('organization_product_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_id')->nullable()->after('product_id');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('monthly_amount');
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        $connection = config('database.platform_connection', 'platform');

        Schema::connection($connection)->table('organization_product_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['partner_id']);
            $table->dropColumn(['partner_id', 'commission_percent']);
        });

        Schema::connection($connection)->dropIfExists('partners');
    }
};
