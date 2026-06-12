<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->unsignedSmallInteger('half_day_minutes')->default(240);
            $table->unsignedSmallInteger('full_day_minutes')->default(480);
            $table->json('working_days')->nullable();
            $table->boolean('is_night_shift')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        $organizations = DB::table('organizations')->select('id')->get();
        foreach ($organizations as $organization) {
            DB::table('hr_shifts')->insert([
                'organization_id' => $organization->id,
                'name' => 'General Shift',
                'code' => 'GENERAL',
                'start_time' => '09:30:00',
                'end_time' => '18:30:00',
                'grace_minutes' => 15,
                'half_day_minutes' => 240,
                'full_day_minutes' => 480,
                'working_days' => json_encode(['mon', 'tue', 'wed', 'thu', 'fri']),
                'is_night_shift' => false,
                'status' => 'active',
                'description' => 'Default office shift.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
