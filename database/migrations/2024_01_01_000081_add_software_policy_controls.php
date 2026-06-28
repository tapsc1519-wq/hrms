<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('policy_status')->default('unreviewed')->after('trusted_publisher');
            $table->text('policy_notes')->nullable()->after('policy_status');
            $table->foreignId('policy_reviewed_by')->nullable()->after('policy_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('policy_reviewed_at')->nullable()->after('policy_reviewed_by');
            $table->index(['organization_id', 'policy_status'], 'software_org_policy_idx');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropIndex('software_org_policy_idx');
            $table->dropConstrainedForeignId('policy_reviewed_by');
            $table->dropColumn(['policy_status', 'policy_notes', 'policy_reviewed_at']);
        });
    }
};
