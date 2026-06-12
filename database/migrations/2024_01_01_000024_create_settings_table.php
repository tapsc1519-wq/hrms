<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed defaults
        $now = now();
        DB::table('settings')->insert([
            ['key' => 'site_title',    'value' => 'ITAM',             'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_subtitle', 'value' => 'Asset Management', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_logo',     'value' => null,               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_favicon',  'value' => null,               'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
