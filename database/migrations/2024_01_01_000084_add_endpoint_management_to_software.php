<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('winget_package_id')->nullable()->after('trusted_publisher');
            $table->boolean('endpoint_management_enabled')->default(false)->after('winget_package_id');
            $table->index(['organization_id', 'endpoint_management_enabled'], 'software_endpoint_management_idx');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropIndex('software_endpoint_management_idx');
            $table->dropColumn(['winget_package_id', 'endpoint_management_enabled']);
        });
    }
};
