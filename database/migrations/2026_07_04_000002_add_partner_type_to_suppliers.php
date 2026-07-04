<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'partner_type')) {
                $table->string('partner_type')->default('supplier')->after('user_id');
                $table->index(['organization_id', 'partner_type', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'partner_type')) {
                $table->dropIndex(['organization_id', 'partner_type', 'status']);
                $table->dropColumn('partner_type');
            }
        });
    }
};
