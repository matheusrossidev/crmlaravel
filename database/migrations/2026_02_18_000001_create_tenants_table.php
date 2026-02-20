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

        // Add FK from users.tenant_id → tenants.id (deferred here because tenants
        // must exist before the constraint can reference it, but the users table
        // migration runs first due to its 0001_01_01 date prefix)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::dropIfExists('tenants');
    }
};
