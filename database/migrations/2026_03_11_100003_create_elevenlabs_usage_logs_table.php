<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elevenlabs_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedInteger('characters_used');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('tts_characters_exhausted')->default(false)->after('ai_tokens_exhausted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elevenlabs_usage_logs');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('tts_characters_exhausted');
        });
    }
};
