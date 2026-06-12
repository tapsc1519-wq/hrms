<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('personal_email');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('ifsc_code', 20)->nullable()->after('bank_account_number');
            $table->string('pan_number', 20)->nullable()->after('ifsc_code');
            $table->string('uan_number', 30)->nullable()->after('pan_number');
            $table->string('pf_number', 40)->nullable()->after('uan_number');
            $table->string('esi_number', 40)->nullable()->after('pf_number');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'ifsc_code',
                'pan_number',
                'uan_number',
                'pf_number',
                'esi_number',
            ]);
        });
    }
};
