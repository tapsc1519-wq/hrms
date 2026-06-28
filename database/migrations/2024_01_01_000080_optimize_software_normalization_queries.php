<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->index(
                ['organization_id', 'status', 'is_installed', 'raw_name'],
                'sw_disc_normalization_queue_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->dropIndex('sw_disc_normalization_queue_idx');
        });
    }
};
