<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->enum('type', ['earning', 'deduction'])->default('earning');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_statutory')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code'], 'payroll_components_org_code_unique');
        });

        $defaults = [
            ['Basic Salary', 'BASIC', 'earning', true],
            ['House Rent Allowance', 'HRA', 'earning', false],
            ['Special Allowance', 'SPECIAL_ALLOWANCE', 'earning', false],
            ['Provident Fund', 'PF', 'deduction', true],
            ['ESI', 'ESI', 'deduction', true],
            ['Professional Tax', 'PROFESSIONAL_TAX', 'deduction', true],
            ['TDS', 'TDS', 'deduction', true],
        ];

        foreach (DB::table('organizations')->select('id')->get() as $organization) {
            foreach ($defaults as [$name, $code, $type, $statutory]) {
                DB::table('payroll_components')->insert([
                    'organization_id' => $organization->id,
                    'name' => $name,
                    'code' => $code,
                    'type' => $type,
                    'status' => 'active',
                    'is_statutory' => $statutory,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
