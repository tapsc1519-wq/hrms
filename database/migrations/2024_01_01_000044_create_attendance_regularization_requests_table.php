<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_regularization_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('request_type', ['missed_sign_in', 'missed_sign_out', 'time_correction', 'work_from_home', 'other'])->default('time_correction');
            $table->timestamp('requested_sign_in_at')->nullable();
            $table->timestamp('requested_sign_out_at')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'attendance_date'], 'att_reg_org_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_regularization_requests');
    }
};
