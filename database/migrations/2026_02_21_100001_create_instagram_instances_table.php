<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_account_id', 30)->unique();
            $table->string('username', 100)->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('status', ['disconnected', 'connected'])->default('disconnected');
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_instances');
    }
};
