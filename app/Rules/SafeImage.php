<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates uploaded images using magic bytes (finfo) instead of trusting
 * the client-provided MIME type or extension. Blocks SVG (XSS risk).
 */
class SafeImage implements ValidationRule
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('Arquivo inválido.');
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($value->path());

        if (!in_array($realMime, self::ALLOWED_MIMES, true)) {
            $fail('Tipo de imagem não permitido. Use JPG, PNG, GIF ou WebP.');
        }
    }
}
