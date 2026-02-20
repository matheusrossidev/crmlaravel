<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['facebook', 'google']);
            $table->string('platform_user_id')->nullable();
            $table->string('platform_user_name')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes_json')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_connections');
    }
};
