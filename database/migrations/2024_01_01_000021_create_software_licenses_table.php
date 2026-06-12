<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->enum('license_type', [
                'perpetual','subscription','concurrent',
                'per_seat','per_device','oem','volume','open_source','freeware'
            ])->default('per_seat');

            $table->string('license_key')->nullable();
            $table->unsignedInteger('seats')->default(1);     // total licensed seats
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('po_number')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active','expired','cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
