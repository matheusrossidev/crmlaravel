<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\Task;
use App\Services\NurtureSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FormSubmissionService
{
    public function __construct(
        private readonly FormLeadCreator $leadCreator,
        private readonly FormNotifier $notifier,
    ) {}

    /**
     * Process a form submission end-to-end.
     *
     * @throws ValidationException
     * @throws \RuntimeException
     */
    public function process(Form $form, array $data, string $ip, ?string $userAgent): FormSubmission
    {
        if (! $form->isAcceptingSubmissions()) {
            throw new \RuntimeException('Este formulário não está aceitando submissões no momento.');
        }

        // Honeypot check
        if (! empty($data['_website_url'])) {
            throw new \RuntimeException('Spam detected.');
        }

        // Validate required fields
        $this->validateRequiredFields($form, $data);

        return DB::transaction(function () use ($form, $data, $ip, $userAgent) {
            // 1. Create lead
            $lead = $this->leadCreator->create($form, $data);

            // 2. Save submission
            $submission = FormSubmission::withoutGlobalScope('tenant')->create([
                'form_id'      => $form->id,
                'tenant_id'    => $form->tenant_id,
                'lead_id'      => $lead?->id,
                'data'         => $data,
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
                'submitted_at' => now(),
            ]);

            // 3. Increment view→submission tracking
            Form::withoutGlobalScope('tenant')
                ->where('id', $form->id)
                ->increment('views_count', 0); // no-op, actual views tracked separately

            // 4. Post-submission actions
            if ($lead) {
                $this->executePostActions($form, $lead);
            }

            // 5. Notify
            $this->notifier->notifySubmission($form, $submission, $lead);

            Log::info('FormSubmission: processado', [
                'form_id'      => $form->id,
                'submission_id' => $submission->id,
                'lead_id'      => $lead?->id,
            ]);

            return $submission;
        });
    }

    private function validateRequiredFields(Form $form, array $data): void
    {
        $errors = [];
        foreach ($form->fields ?? [] as $field) {
            if (($field['required'] ?? false) && empty($data[$field['id'] ?? ''])) {
                $label = $field['label'] ?? $field['id'] ?? 'Campo';
                $errors[$field['id']] = ["{$label} é obrigatório."];
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function executePostActions(Form $form, Lead $lead): void
    {
        // Enroll in nurture sequence
        if ($form->sequence_id) {
            try {
                $sequence = \App\Models\NurtureSequence::withoutGlobalScope('tenant')->find($form->sequence_id);
                if ($sequence && $sequence->is_active) {
                    app(NurtureSequenceService::class)->enroll($lead, $sequence);
                }
            } catch (\Throwable $e) {
                Log::warning('FormSubmission: erro ao inscrever em sequência', ['error' => $e->getMessage()]);
            }
        }

        // Add to static list
        if ($form->list_id) {
            try {
                DB::table('lead_list_members')->insertOrIgnore([
                    'lead_id'      => $lead->id,
                    'lead_list_id' => $form->list_id,
                    'added_at'     => now(),
                ]);
            } catch (\Throwable) {}
        }

        // Create task for assigned user
        if ($form->create_task && $form->assigned_user_id) {
            try {
                Task::withoutGlobalScope('tenant')->create([
                    'tenant_id'   => $form->tenant_id,
                    'subject'     => "Novo lead via formulário: {$lead->name}",
                    'type'        => 'task',
                    'status'      => 'pending',
                    'priority'    => 'medium',
                    'due_date'    => now()->addDays($form->task_days_offset ?? 1),
                    'lead_id'     => $lead->id,
                    'assigned_to' => $form->assigned_user_id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FormSubmission: erro ao criar tarefa', ['error' => $e->getMessage()]);
            }
        }
    }
}
