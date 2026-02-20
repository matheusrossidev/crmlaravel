<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('waha_message_id')->nullable()->index();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'image', 'audio', 'video', 'document', 'sticker', 'reaction', 'note']);
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime', 100)->nullable();
            $table->string('media_filename')->nullable();
            $table->json('reaction_data')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('ack', ['pending', 'sent', 'delivered', 'read'])->default('pending');
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['conversation_id', 'sent_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
