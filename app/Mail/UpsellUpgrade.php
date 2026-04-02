<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\UpsellTrigger;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpsellUpgrade extends Mailable
{
    use Queueable, SerializesModels;

    public string $checkoutUrl;
    public string $ctaText;
    public string $title;
    public string $body;
    public string $targetPlanName;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly UpsellTrigger $trigger,
    ) {
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);

        $config = $trigger->action_config ?? [];

        $this->checkoutUrl    = route('billing.checkout', ['plan' => $trigger->target_plan]);
        $this->ctaText        = $config['cta_text'] ?? 'Ver plano';
        $this->title          = $config['title'] ?? 'Hora de crescer!';
        $this->body           = $config['body'] ?? 'Você está chegando no limite do seu plano atual. Conheça opções maiores.';
        $this->targetPlanName = $trigger->targetPlanDefinition()?->display_name ?? $trigger->target_plan;
    }

    public function envelope(): Envelope
    {
        $config  = $this->trigger->action_config ?? [];
        $subject = $config['email_subject'] ?? __('email.upsell.subject', ['plan' => $this->targetPlanName]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.upsell-upgrade');
    }
}
