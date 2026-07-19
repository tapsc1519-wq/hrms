<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.platform_connection', 'platform');

        if (! Schema::connection($connection)->hasTable('products')) {
            return;
        }

        DB::connection($connection)->table('products')->updateOrInsert(
            ['slug' => 'erp'],
            [
                'name' => 'Niyantron ERP',
                'short_name' => 'ERP',
                'domain' => 'erp.niyantron.com',
                'app_path' => '/launch/erp',
                'icon' => 'bi-building-gear',
                'color' => 'indigo',
                'description' => 'Enterprise resource planning for procurement, inventory, TRC, sales, finance and operations.',
                'status' => 'active',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $connection = config('database.platform_connection', 'platform');

        if (Schema::connection($connection)->hasTable('products')) {
            DB::connection($connection)->table('products')->where('slug', 'erp')->delete();
        }
    }
};
