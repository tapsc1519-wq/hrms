<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('sign_in_at')->nullable();
            $table->timestamp('sign_out_at')->nullable();
            $table->unsignedInteger('work_minutes')->default(0);
            $table->enum('status', ['present', 'half_day', 'absent'])->default('present');
            $table->string('sign_in_ip')->nullable();
            $table->string('sign_out_ip')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'attendance_date'], 'att_org_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
