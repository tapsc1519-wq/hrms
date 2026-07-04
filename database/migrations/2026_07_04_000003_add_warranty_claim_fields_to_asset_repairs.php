<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_repairs', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_repairs', 'warranty_provider_type')) {
                $table->string('warranty_provider_type')->nullable()->after('market_vendor_address');
                $table->string('warranty_provider_name')->nullable()->after('warranty_provider_type');
                $table->string('warranty_provider_phone')->nullable()->after('warranty_provider_name');
                $table->string('warranty_claim_number')->nullable()->after('warranty_provider_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_repairs', function (Blueprint $table) {
            foreach (['warranty_claim_number', 'warranty_provider_phone', 'warranty_provider_name', 'warranty_provider_type'] as $column) {
                if (Schema::hasColumn('asset_repairs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
