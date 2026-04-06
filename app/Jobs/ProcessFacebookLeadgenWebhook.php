<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\FacebookLeadFormConnection;
use App\Models\FacebookLeadFormEntry;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Services\AutomationEngine;
use App\Services\DuplicateLeadDetector;
use App\Services\FacebookLeadAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFacebookLeadgenWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    /** @var int[] */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly array $payload,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $formId    = $this->payload['form_id'];
        $leadgenId = $this->payload['leadgen_id'];
        $pageId    = $this->payload['page_id'];
        $adId      = $this->payload['ad_id'] ?? '';

        // 1. Find the form connection (resolves tenant)
        $connection = FacebookLeadFormConnection::withoutGlobalScope('tenant')
            ->where('form_id', $formId)
            ->where('page_id', $pageId)
            ->where('is_active', true)
            ->first();

        if (! $connection) {
            Log::info('FacebookLeadgen: no active connection for form', ['form_id' => $formId, 'page_id' => $pageId]);
            return;
        }

        $tenantId = $connection->tenant_id;

        // 2. Dedup check
        $exists = FacebookLeadFormEntry::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('meta_lead_id', $leadgenId)
            ->exists();

        if ($exists) {
            Log::debug('FacebookLeadgen: duplicate lead skipped', ['leadgen_id' => $leadgenId]);
            return;
        }

        // 3. Fetch lead data from Meta API
        $pageToken = $connection->page_access_token;
        if (! $pageToken) {
            $this->logEntry($connection, $leadgenId, 'fb', $adId, null, [], 'failed', 'No page access token');
            return;
        }

        $service  = new FacebookLeadAdsService($pageToken);
        $leadData = $service->getLeadData($leadgenId, $pageToken);

        if (! $leadData || empty($leadData['field_data'])) {
            $this->logEntry($connection, $leadgenId, 'fb', $adId, null, $leadData ?? [], 'failed', 'Could not fetch lead data from Meta');
            return;
        }

        $platform = $leadData['platform'] ?? 'fb'; // 'fb' or 'ig'

        // 4. Map Meta fields to flat key→value
        $metaFields = [];
        foreach ($leadData['field_data'] as $field) {
            $key   = $field['name'] ?? '';
            $value = $field['values'][0] ?? '';
            if ($key && $value !== '') {
                $metaFields[$key] = $value;
            }
        }

        // 5. Apply field mapping
        $mapping    = $connection->field_mapping ?? [];
        $leadFields = ['source' => $platform === 'ig' ? 'instagram_lead_ad' : 'facebook_lead_ad'];
        $customFieldsToSave = [];
        $tagsToAdd = [];

        foreach ($mapping as $metaKey => $crmField) {
            $value = $metaFields[$metaKey] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (str_starts_with($crmField, 'custom:')) {
                $fieldId = (int) substr($crmField, 7);
                $customFieldsToSave[$fieldId] = $value;
            } elseif ($crmField === 'tags') {
                $tagsToAdd = array_merge($tagsToAdd, array_map('trim', explode(',', $value)));
            } elseif (in_array($crmField, ['name', 'email', 'phone', 'company', 'value'], true)) {
                $leadFields[$crmField] = $crmField === 'value'
                    ? (is_numeric(str_replace(['.', ','], ['', '.'], $value)) ? (float) str_replace(['.', ','], ['', '.'], $value) : null)
                    : $value;
            }
        }

        // Merge default tags
        $defaultTags = $connection->default_tags ?? [];
        $allTags = array_unique(array_merge($defaultTags, $tagsToAdd));

        // 6. UTM tracking from ad campaign
        $utmSource   = $platform === 'ig' ? 'instagram' : 'facebook';
        $utmMedium   = 'lead_ad';
        $utmCampaign = null;
        $campaignNameMeta = null;

        if ($adId) {
            try {
                $oauthConn = $connection->oauthConnection;
                if ($oauthConn) {
                    $userService = new FacebookLeadAdsService(decrypt($oauthConn->access_token));
                    $campaign    = $userService->getCampaignFromAd($adId);
                    if ($campaign) {
                        $utmCampaign = $campaign['campaign_name'] ?? null;
                        $campaignNameMeta = $utmCampaign;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('FacebookLeadgen: campaign fetch failed', ['ad_id' => $adId, 'error' => $e->getMessage()]);
            }
        }

        // 7. Duplicate detection
        $detector   = new DuplicateLeadDetector();
        $duplicates = $detector->findDuplicatesFromData([
            'name'  => $leadFields['name'] ?? '',
            'phone' => $leadFields['phone'] ?? '',
            'email' => $leadFields['email'] ?? '',
        ], $tenantId);

        $strongDuplicate = $duplicates->filter(fn ($d) => $d['score'] >= 80)->first();

        if ($strongDuplicate) {
            $this->logEntry($connection, $leadgenId, $platform, $adId, $strongDuplicate['lead']->id, $leadData, 'duplicate', 'Score: ' . $strongDuplicate['score']);
            Log::info('FacebookLeadgen: duplicate detected', ['leadgen_id' => $leadgenId, 'existing_lead' => $strongDuplicate['lead']->id]);
            return;
        }

        // 8. Plan limit check
        $tenant = \App\Models\Tenant::find($tenantId);
        if ($tenant) {
            $limitError = \App\Services\PlanLimitChecker::check('leads', $tenant);
            if ($limitError) {
                $this->logEntry($connection, $leadgenId, $platform, $adId, null, $leadData, 'skipped', $limitError);
                return;
            }
        }

        // 9. Create lead — sanitize fields to respect column lengths
        $phone = $leadFields['phone'] ?? null;
        if ($phone) {
            // Keep only digits and + — Meta test tool sends placeholder text
            $phoneDigits = preg_replace('/[^\d+]/', '', $phone);
            $phone = $phoneDigits ? mb_substr($phoneDigits, 0, 30) : null;
        }

        $lead = Lead::withoutGlobalScope('tenant')->create([
            'tenant_id'    => $tenantId,
            'name'         => mb_substr($leadFields['name'] ?? 'Lead Facebook', 0, 191),
            'phone'        => $phone,
            'email'        => isset($leadFields['email']) && filter_var($leadFields['email'], FILTER_VALIDATE_EMAIL)
                ? strtolower($leadFields['email'])
                : null,
            'company'      => isset($leadFields['company']) ? mb_substr($leadFields['company'], 0, 191) : null,
            'value'        => $leadFields['value'] ?? null,
            'source'       => $leadFields['source'],
            'pipeline_id'  => $connection->pipeline_id,
            'stage_id'     => $connection->stage_id,
            'assigned_to'  => $connection->auto_assign_to,
            'tags'         => ! empty($allTags) ? $allTags : null,
            'utm_source'   => $utmSource,
            'utm_medium'   => $utmMedium,
            'utm_campaign' => $utmCampaign,
        ]);

        // 10. Create LeadEvent
        LeadEvent::create([
            'tenant_id'    => $tenantId,
            'lead_id'      => $lead->id,
            'event_type'   => 'created',
            'description'  => $platform === 'ig'
                ? 'Lead criado via formulário Instagram Lead Ad'
                : 'Lead criado via formulário Facebook Lead Ad',
            'performed_by' => null,
            'created_at'   => now(),
        ]);

        // 11. Save custom field values
        foreach ($customFieldsToSave as $fieldId => $val) {
            $def = CustomFieldDefinition::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', $fieldId)
                ->where('is_active', true)
                ->first();

            if (! $def) {
                continue;
            }

            $cfData = ['tenant_id' => $tenantId, 'lead_id' => $lead->id, 'field_id' => $def->id];

            match ($def->field_type) {
                'number', 'currency' => CustomFieldValue::create(array_merge($cfData, [
                    'value_number' => is_numeric(str_replace(['.', ','], ['', '.'], $val))
                        ? (float) str_replace(['.', ','], ['', '.'], $val) : null,
                ])),
                'date' => CustomFieldValue::create(array_merge($cfData, [
                    'value_date' => \Carbon\Carbon::parse($val)->format('Y-m-d'),
                ])),
                'checkbox' => CustomFieldValue::create(array_merge($cfData, [
                    'value_boolean' => in_array(mb_strtolower($val), ['sim', 'yes', '1', 'true', 'x'], true),
                ])),
                'multiselect' => CustomFieldValue::create(array_merge($cfData, [
                    'value_json' => array_map('trim', explode(',', $val)),
                ])),
                default => CustomFieldValue::create(array_merge($cfData, [
                    'value_text' => $val,
                ])),
            };
        }

        // 12. Log entry
        $this->logEntry($connection, $leadgenId, $platform, $adId, $lead->id, $leadData, 'processed', null, $campaignNameMeta);

        // 13. Fire automation
        try {
            AutomationEngine::run('lead_created', [
                'tenant_id' => $tenantId,
                'lead'      => $lead,
            ]);
        } catch (\Throwable $e) {
            Log::debug('FacebookLeadgen: automation failed', ['lead' => $lead->id, 'error' => $e->getMessage()]);
        }

        Log::info('FacebookLeadgen: lead created', [
            'leadgen_id' => $leadgenId,
            'lead_id'    => $lead->id,
            'platform'   => $platform,
            'source'     => $leadFields['source'],
        ]);
    }

    private function logEntry(
        FacebookLeadFormConnection $connection,
        string $leadgenId,
        string $platform,
        string $adId,
        ?int $leadId,
        array $rawData,
        string $status,
        ?string $error = null,
        ?string $campaignName = null,
    ): void {
        FacebookLeadFormEntry::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $connection->tenant_id,
            'connection_id'      => $connection->id,
            'meta_lead_id'       => $leadgenId,
            'lead_id'            => $leadId,
            'platform'           => $platform,
            'ad_id'              => $adId ?: null,
            'campaign_name_meta' => $campaignName,
            'raw_data'           => $rawData,
            'status'             => $status,
            'error_message'      => $error,
        ]);
    }
}
