<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivated extends Mailable
{
    use Queueable, SerializesModels;

    public string $dashboardUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly ?PlanDefinition $plan,
    ) {
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
        $this->dashboardUrl = route('dashboard');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.subscription_activated.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-activated',
        );
    }
}
