<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('item_type')->default('asset')->after('purchase_order_id');
            $table->foreignId('software_id')->nullable()->after('category_id')->constrained('software')->nullOnDelete();
            $table->string('license_type')->nullable()->after('software_id');
            $table->string('subscription_period')->nullable()->after('license_type');
            $table->index(['item_type', 'software_id'], 'po_item_type_software_idx');
        });

        Schema::table('software_requests', function (Blueprint $table) {
            $table->foreignId('purchase_order_item_id')->nullable()->after('software_assignment_id')->constrained()->nullOnDelete();
            $table->index(['organization_id', 'purchase_order_item_id'], 'sw_request_org_po_item_idx');
        });

        Schema::table('software_licenses', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goods_receipt_id');
            $table->dropConstrainedForeignId('purchase_order_item_id');
            $table->dropConstrainedForeignId('purchase_order_id');
        });

        Schema::table('software_requests', function (Blueprint $table) {
            $table->dropIndex('sw_request_org_po_item_idx');
            $table->dropConstrainedForeignId('purchase_order_item_id');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex('po_item_type_software_idx');
            $table->dropConstrainedForeignId('software_id');
            $table->dropColumn(['item_type', 'license_type', 'subscription_period']);
        });
    }
};
