<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_hash', 64)->unique();
            $table->string('key_prefix', 16);
            $table->json('permissions_json')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id']);
            $table->index(['key_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
