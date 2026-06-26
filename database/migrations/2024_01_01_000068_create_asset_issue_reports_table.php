<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_disposal_id')->nullable()->constrained('asset_disposals')->nullOnDelete();
            $table->string('issue_type');
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->date('reported_date');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('description');
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['asset_id', 'status']);
            $table->index(['reported_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_issue_reports');
    }
};
