<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyst_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('type', ['stage_change', 'add_tag', 'add_note', 'fill_field', 'update_lead']);
            $table->json('payload');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->cascadeOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyst_suggestions');
    }
};
