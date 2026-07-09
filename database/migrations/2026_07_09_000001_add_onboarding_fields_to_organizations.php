<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.product_connection', 'opsbridge'))->table('organizations', function (Blueprint $table) {
            $table->timestamp('onboarding_credentials_shared_at')->nullable()->after('last_payment_at');
            $table->timestamp('onboarding_initial_setup_completed_at')->nullable()->after('onboarding_credentials_shared_at');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.product_connection', 'opsbridge'))->table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_credentials_shared_at',
                'onboarding_initial_setup_completed_at',
            ]);
        });
    }
};
