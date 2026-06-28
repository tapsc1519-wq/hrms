<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_renewal_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('software_license_id')->constrained()->cascadeOnDelete();
            $table->string('decision');
            $table->string('status')->default('planned');
            $table->unsignedInteger('target_seats')->nullable();
            $table->decimal('projected_cost', 14, 2)->nullable();
            $table->date('due_date');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rationale');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('actual_seats')->nullable();
            $table->decimal('actual_cost', 14, 2)->nullable();
            $table->date('new_expiry_date')->nullable();
            $table->date('new_renewal_date')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'due_date'], 'renewal_decision_org_status_due_idx');
            $table->index(['software_license_id', 'status'], 'renewal_decision_license_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_renewal_decisions');
    }
};
