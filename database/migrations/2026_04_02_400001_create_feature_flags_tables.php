<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();        // whatsapp, instagram, facebook_leadads, google_calendar
            $table->string('label', 100);                 // Display name
            $table->string('description', 191)->nullable();
            $table->boolean('is_enabled_globally')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('feature_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('feature_flags')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['feature_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_tenant');
        Schema::dropIfExists('feature_flags');
    }
};
