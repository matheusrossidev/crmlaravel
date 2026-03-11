<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_whatsapp_instance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->constrained()->cascadeOnDelete();
            $table->unique(['ai_agent_id', 'whatsapp_instance_id'], 'agent_instance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_whatsapp_instance');
    }
};
