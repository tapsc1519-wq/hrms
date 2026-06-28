<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_prefix', 16);
            $table->string('token_hash', 64)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'revoked_at'], 'agent_token_org_revoked_idx');
        });

        Schema::create('device_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_uuid');
            $table->string('hostname');
            $table->string('serial_number')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('architecture')->nullable();
            $table->string('agent_version')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_inventory_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'device_uuid'], 'device_agent_org_uuid_unique');
            $table->index(['organization_id', 'last_seen_at'], 'device_agent_org_seen_idx');
        });

        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->foreignId('device_agent_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->boolean('is_installed')->default(true)->after('status');
            $table->timestamp('first_seen_at')->nullable()->after('is_installed');
            $table->timestamp('last_seen_at')->nullable()->after('first_seen_at');
            $table->timestamp('uninstalled_at')->nullable()->after('last_seen_at');
            $table->index(['device_agent_id', 'is_installed'], 'sw_disc_agent_installed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->dropIndex('sw_disc_agent_installed_idx');
            $table->dropConstrainedForeignId('device_agent_id');
            $table->dropColumn(['is_installed', 'first_seen_at', 'last_seen_at', 'uninstalled_at']);
        });
        Schema::dropIfExists('device_agents');
        Schema::dropIfExists('agent_api_tokens');
    }
};
