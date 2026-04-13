<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Form;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Tenant;
use App\Services\AutomationEngine;
use App\Services\PlanLimitChecker;
use Illuminate\Support\Facades\Log;

class FormLeadCreator
{
    /**
     * Create a lead from a form submission's mapped data.
     *
     * @param  Form   $form        The form definition
     * @param  array  $submittedData  Raw submitted data keyed by field ID
     * @return Lead|null  null if plan limit reached
     */
    public function create(Form $form, array $submittedData): ?Lead
    {
        $tenant = Tenant::find($form->tenant_id);
        if (! $tenant) {
            return null;
        }

        // Plan limit check
        $limitMsg = PlanLimitChecker::check('leads', $tenant);
        if ($limitMsg) {
            Log::warning('FormLeadCreator: limite de leads atingido', [
                'form_id'   => $form->id,
                'tenant_id' => $form->tenant_id,
            ]);
            return null;
        }

        // Map form fields → lead fields
        $mappings = $form->mappings ?? [];
        $leadData = [
            'tenant_id'    => $form->tenant_id,
            'pipeline_id'  => $form->pipeline_id,
            'stage_id'     => $form->stage_id,
            'assigned_to'  => $form->assigned_user_id,
            'source'       => $form->source_utm ?: 'form',
            'utm_source'   => $submittedData['_utm_source'] ?? null,
            'utm_medium'   => $submittedData['_utm_medium'] ?? null,
            'utm_campaign' => $submittedData['_utm_campaign'] ?? null,
            'utm_term'     => $submittedData['_utm_term'] ?? null,
            'utm_content'  => $submittedData['_utm_content'] ?? null,
            'fbclid'       => $submittedData['_fbclid'] ?? null,
            'gclid'        => $submittedData['_gclid'] ?? null,
        ];

        $customFields = [];
        $tags = [];

        foreach ($mappings as $fieldId => $destination) {
            $value = $submittedData[$fieldId] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (str_starts_with($destination, 'custom:')) {
                $customFieldId = (int) str_replace('custom:', '', $destination);
                $customFields[$customFieldId] = $value;
            } elseif ($destination === 'tags') {
                $tags = array_merge($tags, is_array($value) ? $value : [$value]);
            } elseif (in_array($destination, ['name', 'phone', 'email', 'company', 'value', 'source', 'birthday', 'notes'])) {
                $leadData[$destination] = $value;
            }
        }

        // Ensure name exists
        if (empty($leadData['name'])) {
            $leadData['name'] = $leadData['email'] ?? $leadData['phone'] ?? 'Lead Formulário';
        }

        // Sanitize phone
        if (! empty($leadData['phone'])) {
            $leadData['phone'] = mb_substr(preg_replace('/[^\d+]/', '', $leadData['phone']), 0, 20);
        }

        // Create lead
        $lead = Lead::withoutGlobalScope('tenant')->create($leadData);

        // Tags
        if (! empty($tags)) {
            $lead->attachTagsByName($tags);
            $lead->update(['tags' => $tags]);
        }

        // Custom fields
        $this->saveCustomFields($lead, $customFields);

        // Audit event
        LeadEvent::create([
            'tenant_id'    => $form->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'created',
            'description'  => "Lead criado via formulário: {$form->name}",
            'performed_by' => null,
            'created_at'   => now(),
        ]);

        // Trigger automations
        try {
            (new AutomationEngine())->run('lead_created', [
                'tenant_id' => $form->tenant_id,
                'lead'      => $lead->fresh(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FormLeadCreator: erro na automação', ['error' => $e->getMessage()]);
        }

        return $lead;
    }

    private function saveCustomFields(Lead $lead, array $customFields): void
    {
        foreach ($customFields as $fieldId => $value) {
            $def = CustomFieldDefinition::withoutGlobalScope('tenant')->find($fieldId);
            if (! $def) {
                continue;
            }

            $valueData = match ($def->field_type) {
                'number', 'currency' => ['value_number' => $value !== '' ? (float) $value : null],
                'date'               => ['value_date' => $value ?: null],
                'checkbox'           => ['value_boolean' => (bool) $value],
                'multiselect'        => ['value_json' => is_array($value) ? $value : [$value]],
                default              => ['value_text' => (string) $value],
            };

            CustomFieldValue::withoutGlobalScope('tenant')->updateOrCreate(
                ['lead_id' => $lead->id, 'field_id' => $def->id],
                array_merge($valueData, ['tenant_id' => $lead->tenant_id]),
            );
        }
    }
}
