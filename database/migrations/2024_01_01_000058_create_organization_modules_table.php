<?php

use App\Support\ModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 60);
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'module_key'], 'org_modules_unique');
        });

        $now = now();
        $modules = array_keys(ModuleRegistry::all());

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach ($modules as $moduleKey) {
                DB::table('organization_modules')->insert([
                    'organization_id' => $organizationId,
                    'module_key' => $moduleKey,
                    'is_enabled' => true,
                    'enabled_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_modules');
    }
};
