<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['employee', 'external_buyer', 'vendor_recycler', 'auction_buyer', 'donation_recipient'])->default('external_buyer');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->foreignId('disposal_buyer_id')->nullable()->after('completed_by')->constrained('disposal_buyers')->nullOnDelete();
            $table->enum('payment_status', ['not_required', 'pending', 'partial', 'paid'])->default('not_required')->after('disposal_cost');
            $table->string('handover_reference')->nullable()->after('certificate_number');
        });
    }

    public function down(): void
    {
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disposal_buyer_id');
            $table->dropColumn(['payment_status', 'handover_reference']);
        });

        Schema::dropIfExists('disposal_buyers');
    }
};
