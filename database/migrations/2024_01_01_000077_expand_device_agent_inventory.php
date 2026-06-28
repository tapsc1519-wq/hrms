<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_agents', function (Blueprint $table) {
            $table->json('hardware_info')->nullable();
            $table->json('network_info')->nullable();
            $table->json('security_info')->nullable();
            $table->json('user_info')->nullable();
            $table->unsignedSmallInteger('sync_interval_minutes')->default(60);
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
        });

        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->string('raw_edition')->nullable()->after('raw_version');
            $table->string('raw_build_number')->nullable()->after('raw_edition');
            $table->string('product_code')->nullable()->after('executable');
            $table->text('uninstall_string')->nullable()->after('install_path');
        });
    }

    public function down(): void
    {
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->dropColumn(['raw_edition', 'raw_build_number', 'product_code', 'uninstall_string']);
        });
        Schema::table('device_agents', function (Blueprint $table) {
            $table->dropColumn(['hardware_info', 'network_info', 'security_info', 'user_info', 'sync_interval_minutes', 'last_error', 'last_error_at']);
        });
    }
};
