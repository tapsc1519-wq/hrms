<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('depreciation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['straight_line', 'declining_balance', 'sum_of_years'])->default('straight_line');
            $table->integer('useful_life_years')->default(3);
            $table->decimal('salvage_value', 12, 2)->default(0);
            $table->decimal('depreciation_rate', 5, 2)->default(0);
            $table->date('start_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_records');
    }
};
