<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_handover_requests', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('response_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
        });

        DB::statement("ALTER TABLE asset_handover_requests MODIFY status ENUM('pending','pending_admin','approved','accepted','rejected','admin_rejected','cancelled') NOT NULL DEFAULT 'pending_admin'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE asset_handover_requests MODIFY status ENUM('pending','accepted','rejected','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('asset_handover_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'approval_notes']);
        });
    }
};
