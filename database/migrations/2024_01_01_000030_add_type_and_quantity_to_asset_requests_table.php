<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->string('request_type')->default('new_asset')->after('category_id');
            $table->unsignedInteger('quantity')->default(1)->after('request_type');
        });
    }

    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropColumn(['request_type', 'quantity']);
        });
    }
};
