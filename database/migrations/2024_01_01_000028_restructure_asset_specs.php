<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add spec_template to categories
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->json('spec_template')->nullable()->after('depreciation_years');
        });

        // 2. Add flexible specs JSON to assets
        Schema::table('assets', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('specifications');
        });

        // 3. Migrate existing data from 8 columns → specs JSON (if any rows exist)
        $keys = [
            'spec_processor'    => 'Processor',
            'spec_ram'          => 'RAM',
            'spec_storage'      => 'Storage',
            'spec_display'      => 'Display',
            'spec_os'           => 'Operating System',
            'spec_graphics'     => 'Graphics',
            'spec_battery'      => 'Battery',
            'spec_connectivity' => 'Connectivity',
        ];

        DB::table('assets')->orderBy('id')->each(function ($row) use ($keys) {
            $specs = [];
            foreach ($keys as $col => $label) {
                $val = $row->$col ?? null;
                if (!empty($val)) {
                    $specs[$col] = $val;
                }
            }
            if (!empty($specs)) {
                DB::table('assets')->where('id', $row->id)->update(['specs' => json_encode($specs)]);
            }
        });

        // 4. Drop the 8 fixed spec columns
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_brand_id']);
            $table->dropForeign(['asset_model_id']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'spec_processor', 'spec_ram', 'spec_storage', 'spec_display',
                'spec_os', 'spec_graphics', 'spec_battery', 'spec_connectivity',
            ]);
        });

        // 5. Re-add the FK columns cleanly (without the spec columns)
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'asset_brand_id')) {
                $table->foreignId('asset_brand_id')
                      ->nullable()->after('vendor_id')
                      ->constrained('asset_brands')->nullOnDelete();
            }
            if (!Schema::hasColumn('assets', 'asset_model_id')) {
                $table->foreignId('asset_model_id')
                      ->nullable()->after('asset_brand_id')
                      ->constrained('asset_models')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn('spec_template');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('specs');
        });
    }
};
