<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('organizations')
            ->where('billing_status', 'trial')
            ->update([
                'trial_started_at' => $now,
                'trial_ends_at' => DB::raw("DATE_ADD(NOW(), INTERVAL COALESCE(trial_months, 1) MONTH)"),
            ]);
    }

    public function down(): void
    {
        //
    }
};
