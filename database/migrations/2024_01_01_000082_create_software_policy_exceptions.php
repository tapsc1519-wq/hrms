<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_policy_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('software_discovery_id')->constrained('software_discoveries')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('status')->default('approved');
            $table->date('valid_from');
            $table->date('expires_at');
            $table->text('reason');
            $table->text('conditions')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'expires_at'], 'policy_exception_org_status_expiry_idx');
            $table->index(['software_discovery_id', 'status'], 'policy_exception_discovery_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_policy_exceptions');
    }
};
