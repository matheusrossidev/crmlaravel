<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna `leads.source` nullable pra bater com o LeadRequest (validates `nullable`).
 *
 * Bug reportado em prod (Sentry 7412657199): UPDATE falha com
 *   "Column 'source' cannot be null"
 * quando o frontend envia `source: null` no payload (drawer tem default 'manual'
 * mas outros callers podem omitir/zerar).
 *
 * Mantém o DEFAULT 'manual' pros INSERTs — NULL só passa quando o caller
 * manda explicitamente null (update parcial).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source', 100)->nullable()->default('manual')->change();
        });
    }

    public function down(): void
    {
        // Não reverte: rows criadas com source=null não teriam como voltar.
    }
};
