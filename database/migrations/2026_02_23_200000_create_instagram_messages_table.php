<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('instagram_conversations')->cascadeOnDelete();
            $table->string('ig_message_id', 100)->unique()->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'image', 'audio', 'video', 'document', 'sticker', 'reaction'])->default('text');
            $table->text('body')->nullable();
            $table->text('media_url')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->string('ack', 20)->default('sent');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_messages');
    }
};
