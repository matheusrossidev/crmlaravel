<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingSoon extends Mailable
{
    use Queueable, SerializesModels;

    public string $checkoutUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly int $daysLeft,
    ) {
        $this->checkoutUrl = route('billing.checkout');
    }

    public function envelope(): Envelope
    {
        $urgency = $this->daysLeft === 1 ? 'Último dia!' : "{$this->daysLeft} dias restantes";
        return new Envelope(
            subject: "⏳ Seu trial expira em breve — {$urgency}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending-soon',
        );
    }
}
