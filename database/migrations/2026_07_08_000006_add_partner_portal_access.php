<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','supplier','staff','partner') DEFAULT 'staff'");

        Schema::connection(config('database.platform_connection', 'platform'))->table('partners', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->table('partners', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        DB::table('users')->where('role', 'partner')->update(['role' => 'staff']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','supplier','staff') DEFAULT 'staff'");
    }
};
