<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('holiday_date');
            $table->enum('type', ['public', 'company', 'optional'])->default('public');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'holiday_date', 'name'], 'hr_holidays_org_date_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_holidays');
    }
};
