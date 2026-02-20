<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('plan', 50)->default('free');
            $table->enum('status', ['active', 'inactive', 'trial', 'suspended'])->default('trial');
            $table->json('settings_json')->nullable();
            $table->integer('max_users')->default(3);
            $table->integer('max_leads')->default(500);
            $table->integer('max_pipelines')->default(3);
            $table->integer('max_custom_fields')->default(10);
            $table->integer('api_rate_limit')->default(1000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
