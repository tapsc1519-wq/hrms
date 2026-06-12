<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = DB::table('attendance_records')
            ->whereNotNull('sign_in_at')
            ->get();

        foreach ($records as $record) {
            $exists = DB::table('attendance_sessions')
                ->where('attendance_record_id', $record->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('attendance_sessions')->insert([
                'attendance_record_id' => $record->id,
                'organization_id' => $record->organization_id,
                'employee_profile_id' => $record->employee_profile_id,
                'user_id' => $record->user_id,
                'sign_in_at' => $record->sign_in_at,
                'sign_out_at' => $record->sign_out_at,
                'work_minutes' => (int) ($record->work_minutes ?? 0),
                'sign_in_ip' => $record->sign_in_ip,
                'sign_out_ip' => $record->sign_out_ip,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('attendance_sessions')->truncate();
    }
};
