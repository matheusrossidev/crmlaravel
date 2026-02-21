<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            // URLs do WhatsApp (profile pictures) excedem 191 chars — mudar para TEXT
            $table->text('contact_picture_url')->nullable()->change();
            // phone: VARCHAR(20) é insuficiente para LIDs numéricos longos (até 15 dígitos)
            // e para números com prefixo internacional — ampliar para 30
            $table->string('phone', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            $table->string('contact_picture_url')->nullable()->change();
            $table->string('phone', 20)->change();
        });
    }
};
