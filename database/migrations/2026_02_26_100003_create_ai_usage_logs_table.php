<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('model', 100);
            $table->string('provider', 50);
            $table->unsignedInteger('tokens_prompt')->default(0);
            $table->unsignedInteger('tokens_completion')->default(0);
            $table->unsignedInteger('tokens_total')->default(0);
            $table->string('type', 30)->default('chat'); // chat, knowledge, test
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
