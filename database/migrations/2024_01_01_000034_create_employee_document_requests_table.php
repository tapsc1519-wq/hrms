<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fulfilled_document_id')->nullable()->constrained('employee_documents')->nullOnDelete();
            $table->enum('document_type', [
                'offer_letter',
                'id_proof',
                'address_proof',
                'education',
                'experience',
                'policy_acknowledgement',
                'other',
            ])->default('other');
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_requests');
    }
};
