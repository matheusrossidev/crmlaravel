<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FormNotifier
{
    public function notifySubmission(Form $form, FormSubmission $submission, ?Lead $lead): void
    {
        $emails = $form->notify_emails ?? [];

        if (empty($emails)) {
            return;
        }

        try {
            $leadName  = $lead?->name ?? 'Lead';
            $leadEmail = $lead?->email ?? '-';
            $leadPhone = $lead?->phone ?? '-';
            $formName  = $form->name;

            $subject = "Nova submissão: {$formName}";
            $body    = "Um novo lead foi criado via formulário.\n\n"
                     . "Formulário: {$formName}\n"
                     . "Nome: {$leadName}\n"
                     . "E-mail: {$leadEmail}\n"
                     . "Telefone: {$leadPhone}\n"
                     . "Data: " . now()->format('d/m/Y H:i') . "\n";

            foreach ($emails as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                Mail::raw($body, function ($msg) use ($email, $subject) {
                    $msg->to($email)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('FormNotifier: erro ao enviar email', [
                'form_id' => $form->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
