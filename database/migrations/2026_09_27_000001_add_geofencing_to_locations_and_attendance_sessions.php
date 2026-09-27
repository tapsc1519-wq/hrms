<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('geofence_mode', ['disabled', 'warning', 'strict'])->default('disabled')->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('geofence_mode');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geofence_radius_metres')->default(100)->after('longitude');
            $table->unsignedInteger('maximum_gps_accuracy_metres')->default(100)->after('geofence_radius_metres');
            $table->boolean('require_location_on_sign_in')->default(true)->after('maximum_gps_accuracy_metres');
            $table->boolean('require_location_on_sign_out')->default(true)->after('require_location_on_sign_in');
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->foreignId('work_location_id')->nullable()->after('user_id')->constrained('locations')->nullOnDelete();
            $table->decimal('sign_in_latitude', 10, 7)->nullable()->after('sign_in_ip');
            $table->decimal('sign_in_longitude', 10, 7)->nullable()->after('sign_in_latitude');
            $table->decimal('sign_in_accuracy_metres', 8, 2)->nullable()->after('sign_in_longitude');
            $table->unsignedInteger('sign_in_distance_metres')->nullable()->after('sign_in_accuracy_metres');
            $table->string('sign_in_geofence_status', 24)->default('disabled')->after('sign_in_distance_metres');
            $table->decimal('sign_out_latitude', 10, 7)->nullable()->after('sign_out_ip');
            $table->decimal('sign_out_longitude', 10, 7)->nullable()->after('sign_out_latitude');
            $table->decimal('sign_out_accuracy_metres', 8, 2)->nullable()->after('sign_out_longitude');
            $table->unsignedInteger('sign_out_distance_metres')->nullable()->after('sign_out_accuracy_metres');
            $table->string('sign_out_geofence_status', 24)->default('disabled')->after('sign_out_distance_metres');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_location_id');
            $table->dropColumn([
                'sign_in_latitude', 'sign_in_longitude', 'sign_in_accuracy_metres', 'sign_in_distance_metres', 'sign_in_geofence_status',
                'sign_out_latitude', 'sign_out_longitude', 'sign_out_accuracy_metres', 'sign_out_distance_metres', 'sign_out_geofence_status',
            ]);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'geofence_mode', 'latitude', 'longitude', 'geofence_radius_metres', 'maximum_gps_accuracy_metres',
                'require_location_on_sign_in', 'require_location_on_sign_out',
            ]);
        });
    }
};
