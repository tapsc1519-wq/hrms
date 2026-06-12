<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('user_id')->constrained('hr_shifts')->nullOnDelete();
            $table->unsignedInteger('late_minutes')->default(0)->after('work_minutes');
            $table->unsignedInteger('early_leave_minutes')->default(0)->after('late_minutes');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('early_leave_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['shift_id', 'late_minutes', 'early_leave_minutes', 'overtime_minutes']);
        });
    }
};
