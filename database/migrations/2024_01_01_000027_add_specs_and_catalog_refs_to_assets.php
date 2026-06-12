<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Catalog FK references (after existing vendor_id)
            $table->foreignId('asset_brand_id')
                  ->nullable()->after('vendor_id')
                  ->constrained('asset_brands')->nullOnDelete();
            $table->foreignId('asset_model_id')
                  ->nullable()->after('asset_brand_id')
                  ->constrained('asset_models')->nullOnDelete();

            // Structured specification columns (after existing 'specifications')
            $table->string('spec_processor')->nullable()->after('specifications');
            $table->string('spec_ram')->nullable()->after('spec_processor');
            $table->string('spec_storage')->nullable()->after('spec_ram');
            $table->string('spec_display')->nullable()->after('spec_storage');
            $table->string('spec_os')->nullable()->after('spec_display');
            $table->string('spec_graphics')->nullable()->after('spec_os');
            $table->string('spec_battery')->nullable()->after('spec_graphics');
            $table->string('spec_connectivity')->nullable()->after('spec_battery');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_brand_id']);
            $table->dropForeign(['asset_model_id']);
            $table->dropColumn([
                'asset_brand_id', 'asset_model_id',
                'spec_processor', 'spec_ram', 'spec_storage', 'spec_display',
                'spec_os', 'spec_graphics', 'spec_battery', 'spec_connectivity',
            ]);
        });
    }
};
