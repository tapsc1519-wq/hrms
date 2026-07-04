<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_amc_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('contract_number')->nullable();
            $table->string('title');
            $table->string('coverage_type')->default('service_only');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('response_sla_hours')->nullable();
            $table->unsignedInteger('resolution_sla_hours')->nullable();
            $table->boolean('parts_included')->default(false);
            $table->boolean('onsite_support')->default(false);
            $table->string('document_path')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('asset_amc_contract_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_amc_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['asset_amc_contract_id', 'asset_id'], 'amc_contract_asset_unique');
        });

        Schema::create('asset_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_issue_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('amc_contract_id')->nullable()->constrained('asset_amc_contracts')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('qc_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('repair_number')->unique();
            $table->string('source')->default('admin');
            $table->string('repair_type')->default('internal');
            $table->string('priority')->default('medium');
            $table->string('status')->default('request_raised');
            $table->string('market_vendor_name')->nullable();
            $table->string('market_vendor_contact')->nullable();
            $table->string('market_vendor_phone')->nullable();
            $table->text('market_vendor_address')->nullable();
            $table->text('issue_summary');
            $table->text('diagnosis')->nullable();
            $table->text('work_performed')->nullable();
            $table->date('requested_date');
            $table->date('sent_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('service_cost', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('invoice_number')->nullable();
            $table->string('invoice_path')->nullable();
            $table->string('qc_status')->nullable();
            $table->json('qc_checks')->nullable();
            $table->text('qc_notes')->nullable();
            $table->timestamp('qc_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['asset_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('asset_repair_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_repair_id')->constrained()->cascadeOnDelete();
            $table->string('part_name');
            $table->string('part_number')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_repair_parts');
        Schema::dropIfExists('asset_repairs');
        Schema::dropIfExists('asset_amc_contract_assets');
        Schema::dropIfExists('asset_amc_contracts');
    }
};
