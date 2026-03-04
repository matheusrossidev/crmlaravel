<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content');
            $table->timestamp('sent_at');

            $table->foreign('conversation_id')->references('id')->on('website_conversations')->cascadeOnDelete();
            $table->index(['conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_messages');
    }
};
