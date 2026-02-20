<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url', 2000);
            $table->string('secret')->nullable();
            $table->json('events_json');
            $table->json('headers_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('retry_count')->default(3);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_config_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->json('payload_json');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->enum('status', ['success', 'failed', 'pending', 'retrying'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['webhook_config_id', 'created_at']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_configs');
    }
};
