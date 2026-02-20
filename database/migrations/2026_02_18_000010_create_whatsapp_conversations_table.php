<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('whatsapp_message_id')->nullable();
            $table->string('referral_source')->nullable();
            $table->foreignId('referral_campaign_id')->nullable()->references('id')->on('campaigns')->nullOnDelete();
            $table->enum('status', ['open', 'closed', 'expired'])->default('open');
            $table->timestamp('started_at');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'started_at']);
            $table->index(['tenant_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
