<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('domain')->nullable();
            $table->string('app_path')->nullable();
            $table->string('icon')->default('bi-grid-1x2-fill');
            $table->string('color')->default('blue');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'coming_soon'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('products')->insert([
            'name' => 'OpsBridge',
            'slug' => 'opsbridge',
            'short_name' => 'OpsBridge',
            'domain' => 'opsbridge.niyantron.com',
            'app_path' => '/admin/dashboard',
            'icon' => 'bi-diagram-3-fill',
            'color' => 'blue',
            'description' => 'ITAM, SAM, HRMS, Payroll, Endpoint Management, AMC and Asset Disposal for organizations.',
            'status' => 'active',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
