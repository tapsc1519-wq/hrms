<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_compliance_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('software_compliance_actions', 'software_discovery_id')) {
                $table->foreignId('software_discovery_id')->nullable()->after('software_id')->constrained('software_discoveries')->nullOnDelete();
            }
            if (! Schema::hasColumn('software_compliance_actions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('software_discovery_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('software_compliance_actions', 'asset_id')) {
                $table->foreignId('asset_id')->nullable()->after('user_id')->constrained('assets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('software_compliance_actions', function (Blueprint $table) {
            if (Schema::hasColumn('software_compliance_actions', 'asset_id')) {
                $table->dropConstrainedForeignId('asset_id');
            }
            if (Schema::hasColumn('software_compliance_actions', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('software_compliance_actions', 'software_discovery_id')) {
                $table->dropConstrainedForeignId('software_discovery_id');
            }
        });
    }
};
