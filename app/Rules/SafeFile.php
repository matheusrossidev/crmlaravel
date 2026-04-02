<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates uploaded files using magic bytes (finfo) to ensure content
 * matches declared type. Blocks executable files and scripts.
 */
class SafeFile implements ValidationRule
{
    /** MIME types that are NEVER allowed regardless of context. */
    private const BLOCKED_MIMES = [
        'application/x-httpd-php',
        'application/x-php',
        'text/x-php',
        'application/x-executable',
        'application/x-msdos-program',
        'application/x-msdownload',
        'application/x-shellscript',
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',        // SVG can contain JS
        'application/javascript',
        'text/javascript',
    ];

    /** @var string[] Allowed MIME types (if set, only these pass) */
    private array $allowedMimes;

    /**
     * @param string[] $allowedMimes If provided, only these MIME types are accepted.
     */
    public function __construct(array $allowedMimes = [])
    {
        $this->allowedMimes = $allowedMimes;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('Arquivo inválido.');
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($value->path());

        // Always block dangerous files
        if (in_array($realMime, self::BLOCKED_MIMES, true)) {
            $fail('Tipo de arquivo bloqueado por segurança.');
            return;
        }

        // If allowlist is set, enforce it
        if (!empty($this->allowedMimes) && !in_array($realMime, $this->allowedMimes, true)) {
            $fail('Tipo de arquivo não permitido.');
        }
    }
}
