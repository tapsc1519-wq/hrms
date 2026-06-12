<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY leave_type VARCHAR(60) NOT NULL DEFAULT 'casual'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY leave_type ENUM('casual','sick','earned','unpaid','other') NOT NULL DEFAULT 'casual'");
    }
};
