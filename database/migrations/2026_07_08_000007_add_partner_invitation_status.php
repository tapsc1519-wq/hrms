<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->table('partners', function (Blueprint $table) {
            $table->timestamp('invitation_sent_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->table('partners', function (Blueprint $table) {
            $table->dropColumn('invitation_sent_at');
        });
    }
};
