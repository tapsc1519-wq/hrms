<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_sso_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->boolean('is_enabled')->default(false);
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('tenant')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider'], 'org_sso_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_sso_settings');
    }
};
