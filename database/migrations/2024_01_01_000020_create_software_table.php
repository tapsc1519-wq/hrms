<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // e.g. "Microsoft Office 365"
            $table->string('vendor')->nullable();            // e.g. "Microsoft"
            $table->string('version')->nullable();           // e.g. "2021"
            $table->enum('category', [
                'productivity','security','design','development',
                'communication','database','erp','operating_system','other'
            ])->default('other');
            $table->text('description')->nullable();
            $table->string('publisher_website')->nullable();
            $table->string('icon')->nullable();              // stored logo path
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software');
    }
};
