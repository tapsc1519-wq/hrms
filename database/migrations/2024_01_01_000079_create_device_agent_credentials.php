<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_agent_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_agent_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('key_prefix', 20);
            $table->string('key_hash', 64)->unique();
            $table->timestamp('issued_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'revoked_at'], 'device_credential_org_revoked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_agent_credentials');
    }
};
