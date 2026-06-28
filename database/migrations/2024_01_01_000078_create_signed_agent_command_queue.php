<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_signing_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('encrypted_private_key');
            $table->text('public_key_xml');
            $table->string('fingerprint', 64);
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_agent_id')->constrained()->cascadeOnDelete();
            $table->uuid('command_uuid')->unique();
            $table->string('command_type');
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('priority')->default(5);
            $table->string('status')->default('queued');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['device_agent_id', 'status', 'available_at'], 'agent_command_device_status_idx');
            $table->index(['organization_id', 'created_at'], 'agent_command_org_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commands');
        Schema::dropIfExists('agent_signing_keys');
    }
};
