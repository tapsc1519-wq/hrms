<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_api_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_api_tokens', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('agent_api_tokens', 'purpose')) {
                $table->string('purpose', 50)->default('admin_enrollment')->after('assigned_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_api_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('agent_api_tokens', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }
            if (Schema::hasColumn('agent_api_tokens', 'purpose')) {
                $table->dropColumn('purpose');
            }
        });
    }
};
