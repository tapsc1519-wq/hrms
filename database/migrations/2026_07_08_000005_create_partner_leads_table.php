<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.platform_connection', 'platform');

        Schema::connection($connection)->create('partner_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('converted_organization_id')->nullable();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('expected_monthly_value', 12, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->enum('stage', ['new', 'contacted', 'demo', 'proposal', 'won', 'lost'])->default('new');
            $table->date('expected_close_date')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stage', 'partner_id']);
            $table->index('converted_organization_id');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.platform_connection', 'platform'))->dropIfExists('partner_leads');
    }
};
