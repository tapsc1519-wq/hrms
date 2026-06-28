<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_usage_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('software_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('software_discovery_id')->nullable()->constrained('software_discoveries')->nullOnDelete();
            $table->string('status')->default('pending_user');
            $table->unsignedInteger('inactivity_days')->nullable();
            $table->date('last_used_date')->nullable();
            $table->decimal('estimated_annual_savings', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'sw_usage_review_org_status_idx');
            $table->index(['software_assignment_id', 'status'], 'sw_usage_review_assignment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_usage_reviews');
    }
};
