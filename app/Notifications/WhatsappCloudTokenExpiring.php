<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WhatsappInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica admin do tenant quando o token WhatsApp Cloud está expirando
 * ou já expirou. Disparada por CheckWhatsappCloudTokens.
 *
 * Só dispara UMA VEZ por mudança de status (de valid→expiring, etc)
 * — o command verifica `getOriginal()` antes pra evitar spam diário.
 */
class WhatsappCloudTokenExpiring extends Notification
{
    use Queueable;

    public function __construct(
        public readonly WhatsappInstance $instance,
        public readonly string $status,   // expiring | expired | invalid
        public readonly int $daysLeft = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->instance->label ?: $this->instance->phone_number ?: 'número';

        $subject = match ($this->status) {
            'expired' => "WhatsApp Cloud ({$label}) expirou — reconectar urgente",
            'invalid' => "WhatsApp Cloud ({$label}) inválido — reconectar urgente",
            default   => "WhatsApp Cloud ({$label}) expira em breve",
        };

        $msg = (new MailMessage())
            ->subject($subject)
            ->greeting('Olá ' . ($notifiable->name ?? '') . ',');

        if ($this->status === 'expired' || $this->status === 'invalid') {
            $msg->line("A integração WhatsApp Cloud API do número **{$label}** {$this->statusLine()}.")
                ->line('Suas conversas e envios pelo painel Syncro estão temporariamente bloqueados até você reconectar.')
                ->action('Reconectar agora', route('settings.integrations.index'));
        } else {
            $msg->line("A integração WhatsApp Cloud API do número **{$label}** expira em **{$this->daysLeft} dias**.")
                ->line('Recomendamos reconectar antes disso pra não interromper envios e recebimento de mensagens.')
                ->action('Reconectar', route('settings.integrations.index'));
        }

        return $msg->line('Se precisar de ajuda, responda esse email ou fale com o suporte.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'whatsapp_cloud_token',
            'instance_id'    => $this->instance->id,
            'instance_label' => $this->instance->label,
            'status'         => $this->status,
            'days_left'      => $this->daysLeft,
            'url'            => route('settings.integrations.index'),
            'title'          => match ($this->status) {
                'expired' => 'WhatsApp Cloud expirado',
                'invalid' => 'WhatsApp Cloud inválido',
                default   => 'WhatsApp Cloud expirando',
            },
            'body' => $this->statusLine(),
        ];
    }

    private function statusLine(): string
    {
        return match ($this->status) {
            'expired' => 'expirou',
            'invalid' => 'está inválido',
            default   => "expira em {$this->daysLeft} dias",
        };
    }
}
