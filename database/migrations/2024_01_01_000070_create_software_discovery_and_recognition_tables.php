<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('software_discoveries')) {
            Schema::create('software_discoveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('software_id')->nullable()->constrained('software')->nullOnDelete();
                $table->string('raw_name');
                $table->string('raw_publisher')->nullable();
                $table->string('raw_version')->nullable();
                $table->string('executable')->nullable();
                $table->string('install_path')->nullable();
                $table->date('install_date')->nullable();
                $table->date('last_used_date')->nullable();
                $table->unsignedInteger('usage_count')->nullable();
                $table->unsignedInteger('total_runtime_minutes')->nullable();
                $table->string('source')->default('csv');
                $table->string('status')->default('unknown');
                $table->unsignedTinyInteger('confidence_score')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status'], 'sw_disc_org_status_idx');
                $table->index(['organization_id', 'raw_name'], 'sw_disc_org_raw_idx');
                $table->index(['software_id', 'status'], 'sw_disc_soft_status_idx');
            });
        }

        if (!Schema::hasTable('software_recognition_rules')) {
            Schema::create('software_recognition_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
                $table->string('raw_name_pattern');
                $table->string('raw_publisher_pattern')->nullable();
                $table->unsignedTinyInteger('confidence_score')->default(90);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['organization_id', 'raw_name_pattern'], 'sw_rule_org_raw_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('software_recognition_rules');
        Schema::dropIfExists('software_discoveries');
    }
};
