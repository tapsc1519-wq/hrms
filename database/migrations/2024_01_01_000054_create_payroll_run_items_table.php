<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_salary_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('days_in_month')->default(0);
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('leave_days', 5, 2)->default(0);
            $table->decimal('holiday_days', 5, 2)->default(0);
            $table->decimal('weekly_off_days', 5, 2)->default(0);
            $table->decimal('payable_days', 5, 2)->default(0);
            $table->unsignedInteger('work_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->decimal('gross_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_profile_id'], 'payroll_run_item_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_items');
    }
};
