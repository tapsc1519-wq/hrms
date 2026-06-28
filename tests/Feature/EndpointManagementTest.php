<?php

namespace Tests\Feature;

use App\Models\AgentCommand;
use App\Models\DeviceAgent;
use App\Models\DeviceAgentCredential;
use App\Models\Organization;
use App\Models\Software;
use App\Models\User;
use App\Http\Middleware\ModuleMiddleware;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EndpointManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
        Schema::create('organizations', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug'); $table->string('email'); $table->string('status'); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable(); $table->string('name'); $table->string('email');
            $table->string('password'); $table->string('role'); $table->string('status'); $table->unsignedBigInteger('custom_role_id')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('device_agents', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->string('device_uuid'); $table->string('hostname'); $table->string('status'); $table->timestamps();
        });
        Schema::create('device_agent_credentials', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('device_agent_id');
            $table->string('key_prefix'); $table->string('key_hash'); $table->timestamp('issued_at')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('revoked_at')->nullable(); $table->timestamps();
        });
        Schema::create('software', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->string('name'); $table->string('category'); $table->string('software_type');
            $table->boolean('license_required'); $table->string('criticality'); $table->string('license_metric'); $table->string('winget_package_id')->nullable();
            $table->boolean('endpoint_management_enabled')->default(false); $table->timestamps();
        });
        Schema::create('agent_commands', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('device_agent_id'); $table->uuid('command_uuid');
            $table->string('command_type'); $table->json('payload')->nullable(); $table->unsignedTinyInteger('priority'); $table->string('status');
            $table->timestamp('available_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamp('delivered_at')->nullable();
            $table->timestamp('executed_at')->nullable(); $table->json('result')->nullable(); $table->text('error_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        parent::tearDown();
    }

    public function test_admin_can_queue_an_allowlisted_package_install_for_its_endpoint(): void
    {
        [$admin, $device] = $this->endpointFixture('acme');
        $software = $this->managedSoftware($admin->organization_id, 'Microsoft.VisualStudioCode');
        $response = $this->withoutMiddleware(ModuleMiddleware::class)->actingAs($admin)->post("/admin/agent-sources/{$device->id}/commands/software-install", ['software_id' => $software->id]);

        $response->assertRedirect();
        $command = AgentCommand::sole();
        $this->assertSame('software_install', $command->command_type);
        $this->assertSame('Microsoft.VisualStudioCode', $command->payload['package_id']);
        $this->assertSame($admin->organization_id, $command->organization_id);
    }

    public function test_admin_cannot_queue_software_from_another_organization(): void
    {
        [$admin, $device] = $this->endpointFixture('acme');
        $other = Organization::create(['name' => 'Other', 'slug' => 'other', 'email' => 'other@example.test', 'status' => 'active']);
        $software = $this->managedSoftware($other->id, 'VideoLAN.VLC');
        $response = $this->withoutMiddleware(ModuleMiddleware::class)->actingAs($admin)->post("/admin/agent-sources/{$device->id}/commands/software-install", ['software_id' => $software->id]);

        $response->assertNotFound();
        $this->assertDatabaseCount('agent_commands', 0);
    }

    private function endpointFixture(string $slug): array
    {
        $organization = Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'email' => "{$slug}@example.test", 'status' => 'active']);
        $admin = User::create(['organization_id' => $organization->id, 'name' => 'Admin', 'email' => "admin@{$slug}.test", 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active']);
        $device = DeviceAgent::create(['organization_id' => $organization->id, 'device_uuid' => "device-{$slug}", 'hostname' => "PC-{$slug}", 'status' => 'active']);
        DeviceAgentCredential::create(['organization_id' => $organization->id, 'device_agent_id' => $device->id, 'key_prefix' => 'ops_device_test', 'key_hash' => hash('sha256', "key-{$slug}"), 'issued_at' => now()]);

        return [$admin, $device];
    }

    private function managedSoftware(int $organizationId, string $packageId): Software
    {
        return Software::create([
            'organization_id' => $organizationId, 'name' => $packageId, 'category' => 'development',
            'software_type' => 'freeware', 'license_required' => false, 'criticality' => 'medium',
            'license_metric' => 'per_user', 'winget_package_id' => $packageId, 'endpoint_management_enabled' => true,
        ]);
    }
}
