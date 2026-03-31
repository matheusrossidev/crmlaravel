<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCertificate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'course_id', 'certificate_code',
        'issued_at', 'created_at',
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(PartnerCourse::class, 'course_id');
    }

    public static function generateCode(): string
    {
        do {
            $code = 'SYNC-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (static::where('certificate_code', $code)->exists());

        return $code;
    }
}
