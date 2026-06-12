<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('vendors', 'suppliers');

        // Expand ENUM to include 'supplier', migrate data, then drop 'vendor'
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','vendor','supplier','staff') DEFAULT 'staff'");
        DB::table('users')->where('role', 'vendor')->update(['role' => 'supplier']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','supplier','staff') DEFAULT 'staff'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','supplier','vendor','staff') DEFAULT 'staff'");
        DB::table('users')->where('role', 'supplier')->update(['role' => 'vendor']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','vendor','staff') DEFAULT 'staff'");

        Schema::rename('suppliers', 'vendors');
    }
};
