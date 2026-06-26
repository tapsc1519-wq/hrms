<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('edition')->nullable()->after('version');
            $table->string('software_type')->default('commercial')->after('category');
            $table->boolean('license_required')->default(true)->after('software_type');
            $table->string('criticality')->default('medium')->after('license_required');
            $table->string('license_metric')->default('per_user')->after('criticality');
            $table->boolean('trusted_publisher')->default(false)->after('license_metric');
        });

        Schema::table('software_licenses', function (Blueprint $table) {
            $table->string('purchase_batch')->nullable()->after('license_key');
            $table->string('invoice_number')->nullable()->after('po_number');
            $table->string('agreement_number')->nullable()->after('invoice_number');
            $table->string('subscription_period')->nullable()->after('agreement_number');
            $table->date('renewal_date')->nullable()->after('expiry_date');
            $table->decimal('unit_cost', 12, 2)->nullable()->after('purchase_price');
            $table->string('evidence_document')->nullable()->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_batch',
                'invoice_number',
                'agreement_number',
                'subscription_period',
                'renewal_date',
                'unit_cost',
                'evidence_document',
            ]);
        });

        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn([
                'edition',
                'software_type',
                'license_required',
                'criticality',
                'license_metric',
                'trusted_publisher',
            ]);
        });
    }
};
