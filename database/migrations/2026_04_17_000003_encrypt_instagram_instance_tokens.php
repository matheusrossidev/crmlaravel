<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-encripta access_token de InstagramInstance existentes.
 *
 * Estado anterior: coluna armazenava o token Meta em plaintext.
 * Estado após: coluna armazena `encrypt($token)` com APP_KEY.
 *
 * IMPORTANTE: essa migration é idempotente — se o valor já parecer um payload
 * encriptado (prefixo `eyJ` do JSON base64 do Laravel), pula. Permite re-rodar
 * sem corrupção.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('instagram_instances')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->get(['id', 'access_token']);

        $encrypted = 0;
        $skipped   = 0;

        foreach ($rows as $row) {
            $raw = $row->access_token;

            // Se já parece payload encriptado do Laravel, pula
            if (str_starts_with($raw, 'eyJ')) {
                try {
                    decrypt($raw);
                    $skipped++;
                    continue;
                } catch (\Throwable $e) {
                    // Falhou a decriptação — trata como plaintext mesmo
                }
            }

            try {
                $encryptedValue = encrypt($raw);
                DB::table('instagram_instances')
                    ->where('id', $row->id)
                    ->update(['access_token' => $encryptedValue]);
                $encrypted++;
            } catch (\Throwable $e) {
                Log::error('Falha ao encriptar instagram_instance #' . $row->id, ['error' => $e->getMessage()]);
            }
        }

        Log::info("InstagramInstance encryption: {$encrypted} re-encriptados, {$skipped} já estavam encriptados.");
    }

    public function down(): void
    {
        // Down não-reversível: não vamos desencriptar em plaintext por segurança.
        // Se precisar reverter, APP_KEY ainda pode descriptografar via cast 'encrypted'.
    }
};
