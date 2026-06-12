<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_structure_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_salary_structure_id');
            $table->unsignedBigInteger('payroll_component_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_salary_structure_id', 'payroll_component_id'], 'salary_structure_component_unique');
            $table->foreign('employee_salary_structure_id', 'salary_comp_structure_fk')->references('id')->on('employee_salary_structures')->cascadeOnDelete();
            $table->foreign('payroll_component_id', 'salary_comp_component_fk')->references('id')->on('payroll_components')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_structure_components');
    }
};
