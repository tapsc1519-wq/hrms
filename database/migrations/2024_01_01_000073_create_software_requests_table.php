<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('urgency')->default('normal');
            $table->date('needed_by')->nullable();
            $table->text('business_justification');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('software_license_id')->nullable()->constrained('software_licenses')->nullOnDelete();
            $table->foreignId('software_assignment_id')->nullable()->constrained('software_assignments')->nullOnDelete();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'sw_request_org_status_idx');
            $table->index(['requester_id', 'status'], 'sw_request_user_status_idx');
            $table->index(['software_id', 'status'], 'sw_request_soft_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_requests');
    }
};
