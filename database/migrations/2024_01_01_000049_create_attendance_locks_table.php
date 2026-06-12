<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7);
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('locked_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'month'], 'attendance_locks_org_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_locks');
    }
};
