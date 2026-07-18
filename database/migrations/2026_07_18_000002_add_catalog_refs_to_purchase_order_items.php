<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('asset_brand_id')->nullable()->after('category_id')->constrained('asset_brands')->nullOnDelete();
            $table->foreignId('asset_model_id')->nullable()->after('asset_brand_id')->constrained('asset_models')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_model_id');
            $table->dropConstrainedForeignId('asset_brand_id');
        });
    }
};
