<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ReengagementTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReengagementEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedBody;
    public string $userName;
    public string $loginUrl;

    public function __construct(
        public User $user,
        public Tenant $tenant,
        public ReengagementTemplate $template,
        public array $variables,
    ) {
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
        $this->renderedBody = $template->render($variables);
        $this->userName = $user->name;
        $this->loginUrl = config('app.url', 'https://app.syncro.chat');
    }

    public function envelope(): Envelope
    {
        // Subject vem do template (se setado) OU da lang key bilingual (fallback).
        // A lang key `email.reengagement.title` existe em pt_BR e en, então o
        // fallback respeita o locale setado no construtor via $this->locale().
        $subject = $this->template->subject
            ?: __('email.reengagement.title', ['name' => $this->user->name]);

        // Interpolar variáveis ({{nome}}, {{empresa}}, etc) no subject
        foreach ($this->variables as $key => $value) {
            $subject = str_replace($key, (string) $value, $subject);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reengagement');
    }
}
