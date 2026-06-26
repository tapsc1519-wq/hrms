<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('receipt_number');
            $table->date('received_date');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('delivery_note_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'receipt_number']);
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('received_quantity');
            $table->unsignedInteger('rejected_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->string('acquisition_source')->default('manual')->after('organization_id');
            $table->foreignId('purchase_order_id')->nullable()->after('acquisition_source')->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->after('purchase_order_item_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goods_receipt_id');
            $table->dropConstrainedForeignId('purchase_order_item_id');
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropColumn('acquisition_source');
        });

        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
