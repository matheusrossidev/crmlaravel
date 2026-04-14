<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            // Token do System User do BM da Syncro especificamente linkado a essa WABA.
            // Fallback secundário — prioridade é o token GLOBAL do env var (config/services).
            $table->text('system_user_token')->nullable()->after('access_token');

            // Última vez que o comando whatsapp:cloud-token-health checkou a validade.
            $table->timestamp('token_last_checked_at')->nullable()->after('token_expires_at');

            // Status do token: valid / expiring (<7 dias) / expired / invalid.
            // Usado pra banner de alerta na UI e notificações.
            $table->string('token_status', 20)->default('valid')->after('token_last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn(['system_user_token', 'token_last_checked_at', 'token_status']);
        });
    }
};
