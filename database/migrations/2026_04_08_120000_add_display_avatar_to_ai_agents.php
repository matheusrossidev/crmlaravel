<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table): void {
            // Avatar decorativo APENAS pra UI admin (lista de agentes, sidebar do edit).
            // NUNCA enviado pro lead/cliente final. O `bot_avatar` continua exclusivo do widget web chat.
            $table->string('display_avatar', 191)->nullable()->after('bot_avatar');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table): void {
            $table->dropColumn('display_avatar');
        });
    }
};
