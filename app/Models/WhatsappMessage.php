<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'waha_message_id',
        'cloud_message_id',
        'direction',
        'sender_name',
        'type',
        'body',
        'media_url',
        'media_mime',
        'media_filename',
        'reaction_data',
        'user_id',
        'ack',
        'is_deleted',
        'sent_at',
    ];

    protected $casts = [
        'reaction_data' => 'array',
        'sent_at'       => 'datetime',
        'is_deleted'    => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @deprecated Use ->body instead. Existe so como rede de seguranca pra
     * pegar codigo legado que tente acessar ->content (campo de WebsiteMessage).
     *
     * Bug historico: ProcessAiResponse e outros spots usavam ->content em
     * WhatsappMessage, retornando null silencioso e quebrando o Agno chat.
     * Agora qualquer acesso a ->content gera warning no log com stack trace
     * pra alguem encontrar o spot bugado IMEDIATAMENTE.
     *
     * Quando confirmar que zero codigo usa mais ->content, remover esse
     * accessor (em PR futuro com padronizacao body/content entre os 3
     * Message models).
     */
    public function getContentAttribute(): ?string
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = isset($bt[1]) ? ($bt[1]['file'] ?? '?') . ':' . ($bt[1]['line'] ?? '?') : 'unknown';
        \Illuminate\Support\Facades\Log::channel('whatsapp')->warning(
            'WhatsappMessage->content acessado (DEPRECATED, use ->body)',
            [
                'msg_id' => $this->id ?? null,
                'caller' => $caller,
            ]
        );
        return $this->body;
    }
}
