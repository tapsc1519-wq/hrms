<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('software_compliance_actions')) {
            Schema::create('software_compliance_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
                $table->string('action_type');
                $table->string('status')->default('open');
                $table->unsignedInteger('quantity')->nullable();
                $table->date('due_date')->nullable();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status'], 'sw_comp_action_org_status_idx');
                $table->index(['software_id', 'action_type'], 'sw_comp_action_soft_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('software_compliance_actions');
    }
};
