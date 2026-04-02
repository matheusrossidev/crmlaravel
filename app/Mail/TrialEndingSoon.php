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
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
        $this->checkoutUrl = route('billing.checkout');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.trial_ending.subject', ['days' => $this->daysLeft]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending-soon',
        );
    }
}
