<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('method', ['scrap', 'sell', 'donate', 'recycle', 'return_to_supplier', 'destroy'])->default('scrap');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->date('disposed_date')->nullable();
            $table->decimal('expected_value', 12, 2)->nullable();
            $table->decimal('recovered_value', 12, 2)->nullable();
            $table->decimal('disposal_cost', 12, 2)->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('certificate_number')->nullable();
            $table->text('reason');
            $table->text('approval_notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
