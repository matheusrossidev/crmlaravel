<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validação de URLs pra prevenir:
 *   - Open redirect (javascript:/data:/vbscript: schemes)
 *   - SSRF (IPs privados, loopback, link-local, metadata AWS/GCP)
 *
 * API:
 *   UrlSafety::isSafeRedirect($url)    — pra redirects externos de user content
 *   UrlSafety::isSafeOutboundHttp($url) — pra HTTP outbound do server (SSRF)
 */
class UrlSafety
{
    /**
     * URL segura pra redirect de user content (ex: form confirmation_value).
     * Bloqueia schemes perigosos (javascript:, data:, vbscript:), exige http/https.
     * Permite qualquer host público.
     */
    public static function isSafeRedirect(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return false;
        }

        // Rejeita controle chars + espaços — pode esconder scheme malicioso
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * URL segura pra outbound HTTP do server (webhook dispatcher, profile picture
     * downloader, etc). Mais restritiva: rejeita IPs privados + loopback + metadata.
     *
     * Retorna array [safe:bool, reason:string|null, host:string|null].
     */
    public static function isSafeOutboundHttp(string $url): array
    {
        if (! self::isSafeRedirect($url)) {
            return ['safe' => false, 'reason' => 'scheme inválido ou URL malformada', 'host' => null];
        }

        $parts = parse_url($url);
        $host  = strtolower($parts['host']);

        // Resolver host -> IP pra bloquear range privado
        // gethostbynamel retorna array de IPs (IPv4)
        $ips = @gethostbynamel($host);
        if ($ips === false || $ips === []) {
            // Host não resolve — pode ser IPv6 ou DNS down. Se for IP literal, checa direto.
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $ips = [$host];
            } else {
                return ['safe' => false, 'reason' => 'host não resolve', 'host' => $host];
            }
        }

        foreach ($ips as $ip) {
            if (self::isPrivateOrMetadataIp($ip)) {
                return ['safe' => false, 'reason' => "IP privado/metadata bloqueado ({$ip})", 'host' => $host];
            }
        }

        return ['safe' => true, 'reason' => null, 'host' => $host];
    }

    /**
     * Bloqueia: loopback, private ranges (RFC 1918), link-local (incl. AWS metadata
     * 169.254.169.254), multicast, CG-NAT, IPv6 loopback/link-local/ULA.
     */
    private static function isPrivateOrMetadataIp(string $ip): bool
    {
        // filter_var com flags exclui os ranges abaixo — se voltar false, é privado/reservado
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
        return $isPublic === false;
    }
}
