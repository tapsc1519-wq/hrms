<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('holiday_id')->nullable()->after('shift_id')->constrained('hr_holidays')->nullOnDelete();
            $table->enum('day_type', ['workday', 'weekly_off', 'holiday'])->default('workday')->after('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['holiday_id']);
            $table->dropColumn(['holiday_id', 'day_type']);
        });
    }
};
