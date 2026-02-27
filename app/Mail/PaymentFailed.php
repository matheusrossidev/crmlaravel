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

class PaymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    public string $billingUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
    ) {
        $this->billingUrl = route('settings.billing');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Falha no pagamento — regularize seu acesso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
        );
    }
}
