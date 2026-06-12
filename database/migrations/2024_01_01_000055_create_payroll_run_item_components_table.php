<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->enum('type', ['earning', 'deduction']);
            $table->decimal('monthly_amount', 14, 2)->default(0);
            $table->decimal('payable_amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_item_components');
    }
};
