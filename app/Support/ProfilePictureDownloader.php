<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Baixa a foto de perfil de um contato (Instagram, WhatsApp, Facebook) e
 * salva localmente em storage/app/public/profile-pics/. Retorna a URL
 * publica permanente.
 *
 * Motivacao: as URLs de foto de perfil retornadas pelas APIs do Meta/WhatsApp
 * sao do CDN deles (lookaside.fbsbx.com, mmg.whatsapp.net, etc) e tem
 * assinatura que expira em algumas horas. Sem baixar e salvar local, o card
 * da conversa fica sem avatar dias depois.
 *
 * Uso:
 *   $localUrl = ProfilePictureDownloader::download($remoteUrl, 'instagram', $tenantId, $igsid);
 */
class ProfilePictureDownloader
{
    /**
     * @param string $remoteUrl URL remota da foto de perfil
     * @param string $channel   'instagram' | 'whatsapp' | 'facebook'
     * @param int    $tenantId  ID do tenant (usado pra organizar storage)
     * @param string $contactId IGSID, phone ou facebook user id (usado pra dedupe)
     * @return string|null URL publica local OU $remoteUrl como fallback OU null se entrada invalida
     */
    public static function download(?string $remoteUrl, string $channel, int $tenantId, string $contactId): ?string
    {
        if (! $remoteUrl) {
            return null;
        }

        // Se ja e uma URL local (storage do nosso app), nao re-baixa
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl && str_starts_with($remoteUrl, $appUrl)) {
            return $remoteUrl;
        }

        // SSRF protection (F-09): bloqueia IPs privados/loopback/metadata
        $safety = \App\Support\UrlSafety::isSafeOutboundHttp($remoteUrl);
        if (! $safety['safe']) {
            Log::channel($channel)->warning('ProfilePictureDownloader: URL bloqueada por SSRF policy', [
                'channel'    => $channel,
                'tenant_id'  => $tenantId,
                'contact_id' => $contactId,
                'reason'     => $safety['reason'],
            ]);
            return null;
        }

        try {
            $response = Http::timeout(20)->withoutRedirecting()->get($remoteUrl);

            if (! $response->successful()) {
                Log::channel($channel)->warning('Download de foto de perfil falhou', [
                    'channel'    => $channel,
                    'tenant_id'  => $tenantId,
                    'contact_id' => $contactId,
                    'url'        => substr($remoteUrl, 0, 100),
                    'status'     => $response->status(),
                ]);
                return $remoteUrl; // fallback graciosa: usa a URL original
            }

            $binary       = $response->body();
            $contentType  = $response->header('Content-Type') ?? '';
            $extension    = self::extensionFromMime($contentType);

            // Hash do contact_id mantem nome consistente — se a foto mudar
            // sobrescreve o mesmo arquivo (nao acumula lixo).
            $cleanContactId = preg_replace('/[^A-Za-z0-9_\-]/', '', $contactId) ?: 'unknown';
            $filename = sprintf(
                'profile-pics/%s/%d/%s.%s',
                $channel,
                $tenantId,
                $cleanContactId,
                $extension,
            );

            Storage::disk('public')->put($filename, $binary);

            return Storage::disk('public')->url($filename);
        } catch (\Throwable $e) {
            Log::channel($channel)->warning('Excecao ao baixar foto de perfil', [
                'channel'    => $channel,
                'tenant_id'  => $tenantId,
                'contact_id' => $contactId,
                'url'        => substr($remoteUrl, 0, 100),
                'error'      => $e->getMessage(),
            ]);
            return $remoteUrl; // fallback graciosa
        }
    }

    private static function extensionFromMime(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')                              => 'png',
            str_contains($mime, 'webp')                             => 'webp',
            str_contains($mime, 'gif')                              => 'gif',
            default                                                  => 'jpg',
        };
    }
}
