<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAnalystSuggestion;
use App\Models\CustomFieldValue;
use App\Models\EventReminder;
use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadDuplicate;
use App\Models\LeadEvent;
use App\Models\LeadSequence;
use App\Models\ScheduledMessage;
use App\Models\SurveyResponse;
use App\Models\WebsiteConversation;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadMergeService
{
    /**
     * Merge secondary lead into primary lead.
     * Primary survives, secondary is archived with status='merged'.
     */
    public function merge(Lead $primary, Lead $secondary): Lead
    {
        if ($primary->id === $secondary->id) {
            throw new \InvalidArgumentException('Cannot merge a lead with itself.');
        }

        if ($primary->tenant_id !== $secondary->tenant_id) {
            throw new \InvalidArgumentException('Cannot merge leads from different tenants.');
        }

        if ($secondary->status === 'merged') {
            throw new \InvalidArgumentException('Secondary lead has already been merged.');
        }

        return DB::transaction(function () use ($primary, $secondary) {
            // Lock both leads to prevent concurrent merges
            $primary  = Lead::withoutGlobalScope('tenant')->lockForUpdate()->find($primary->id);
            $secondary = Lead::withoutGlobalScope('tenant')->lockForUpdate()->find($secondary->id);

            // 1. Fill empty fields on primary with secondary's data
            $this->fillEmptyFields($primary, $secondary);

            // 2. Merge tags (JSON arrays)
            $this->mergeTags($primary, $secondary);

            // 3. Migrate all relations from secondary to primary
            $this->migrateRelations($primary, $secondary);

            // 4. Recalculate score
            $this->recalculateScore($primary);

            // 5. Create merge event on primary
            LeadEvent::create([
                'tenant_id'    => $primary->tenant_id,
                'lead_id'      => $primary->id,
                'event_type'   => 'merged',
                'description'  => "Lead mesclado com #{$secondary->id} — {$secondary->name}",
                'data_json'    => [
                    'merged_lead_id'   => $secondary->id,
                    'merged_lead_name' => $secondary->name,
                    'merged_lead_phone' => $secondary->phone,
                    'merged_lead_email' => $secondary->email,
                ],
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);

            // 6. Archive secondary
            $secondary->update([
                'status'     => 'merged',
                'merged_into' => $primary->id,
                'merged_at'  => now(),
            ]);

            // 7. Mark any LeadDuplicate records as merged
            LeadDuplicate::where(function ($q) use ($primary, $secondary) {
                $q->where('lead_id_a', min($primary->id, $secondary->id))
                  ->where('lead_id_b', max($primary->id, $secondary->id));
            })->where('status', 'pending')
              ->update([
                  'status'      => 'merged',
                  'reviewed_by' => auth()->id(),
                  'reviewed_at' => now(),
              ]);

            $primary->save();

            Log::info('Lead merge completed', [
                'primary_id'   => $primary->id,
                'secondary_id' => $secondary->id,
                'tenant_id'    => $primary->tenant_id,
            ]);

            return $primary->fresh();
        });
    }

    /**
     * Fill empty/null fields on primary with secondary's values.
     */
    private function fillEmptyFields(Lead $primary, Lead $secondary): void
    {
        $fields = ['phone', 'email', 'company', 'value', 'source', 'birthday',
                    'instagram_username', 'notes',
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                    'fbclid', 'gclid'];

        foreach ($fields as $field) {
            if (empty($primary->$field) && !empty($secondary->$field)) {
                $primary->$field = $secondary->$field;
            }
        }

        // Keep the higher value (deal amount)
        if ($secondary->value && (!$primary->value || $secondary->value > $primary->value)) {
            $primary->value = $secondary->value;
        }
    }

    /**
     * Merge tags from both leads (JSON arrays).
     */
    private function mergeTags(Lead $primary, Lead $secondary): void
    {
        $primaryTags   = $primary->tags ?? [];
        $secondaryTags = $secondary->tags ?? [];
        $merged = array_values(array_unique(array_merge($primaryTags, $secondaryTags)));
        $primary->tags = $merged;
    }

    /**
     * Migrate all foreign-key relations from secondary to primary.
     */
    private function migrateRelations(Lead $primary, Lead $secondary): void
    {
        $sid = $secondary->id;
        $pid = $primary->id;

        // HasMany relations (direct update)
        $secondary->leadNotes()->update(['lead_id' => $pid]);
        $secondary->attachments()->update(['lead_id' => $pid]);
        $secondary->tasks()->update(['lead_id' => $pid]);
        $secondary->contacts()->update(['lead_id' => $pid]);
        $secondary->products()->update(['lead_id' => $pid]);
        $secondary->events()->update(['lead_id' => $pid]);
        $secondary->sales()->update(['lead_id' => $pid]);
        $secondary->lostSales()->update(['lead_id' => $pid]);
        $secondary->scoreLogs()->update(['lead_id' => $pid]);

        // Conversations (nullable FK, query directly)
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);
        InstagramConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);
        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);

        // Other relations
        ScheduledMessage::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);
        SurveyResponse::where('lead_id', $sid)->update(['lead_id' => $pid]);
        EventReminder::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);
        AiAnalystSuggestion::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->update(['lead_id' => $pid]);

        // Custom fields — only migrate fields that primary doesn't have
        $primaryFieldIds = $primary->customFieldValues()->pluck('custom_field_definition_id')->toArray();
        CustomFieldValue::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)
            ->whereNotIn('custom_field_definition_id', $primaryFieldIds)
            ->update(['lead_id' => $pid]);
        // Delete remaining custom fields from secondary (duplicates)
        CustomFieldValue::withoutGlobalScope('tenant')
            ->where('lead_id', $sid)->delete();

        // BelongsToMany: lead lists — merge memberships without duplicating
        $primaryListIds = DB::table('lead_list_members')->where('lead_id', $pid)->pluck('lead_list_id')->toArray();
        DB::table('lead_list_members')
            ->where('lead_id', $sid)
            ->whereNotIn('lead_list_id', $primaryListIds)
            ->update(['lead_id' => $pid]);
        DB::table('lead_list_members')->where('lead_id', $sid)->delete();

        // Nurture sequences — if primary has active sequence, exit secondary's; otherwise migrate
        $primaryHasActive = LeadSequence::where('lead_id', $pid)->where('status', 'active')->exists();
        if ($primaryHasActive) {
            LeadSequence::where('lead_id', $sid)->where('status', 'active')
                ->update(['status' => 'exited']);
        }
        LeadSequence::where('lead_id', $sid)->update(['lead_id' => $pid]);
    }

    /**
     * Recalculate lead score from merged score logs.
     */
    private function recalculateScore(Lead $primary): void
    {
        $totalScore = $primary->scoreLogs()->sum('points');
        $primary->score = max(0, (int) $totalScore);
        $primary->score_updated_at = now();
    }

    /**
     * Get a preview of what the merge would do.
     */
    public function preview(Lead $primary, Lead $secondary): array
    {
        $fieldsToFill = [];
        $fields = ['phone', 'email', 'company', 'value', 'source', 'birthday',
                    'instagram_username', 'notes'];

        foreach ($fields as $field) {
            if (empty($primary->$field) && !empty($secondary->$field)) {
                $fieldsToFill[$field] = $secondary->$field;
            }
        }

        return [
            'primary'   => $primary,
            'secondary' => $secondary,
            'fields_to_fill' => $fieldsToFill,
            'tags_to_add' => array_values(array_diff(
                $secondary->tags ?? [],
                $primary->tags ?? []
            )),
            'relations' => [
                'notes'          => $secondary->leadNotes()->count(),
                'attachments'    => $secondary->attachments()->count(),
                'tasks'          => $secondary->tasks()->count(),
                'contacts'       => $secondary->contacts()->count(),
                'products'       => $secondary->products()->count(),
                'events'         => $secondary->events()->count(),
                'sales'          => $secondary->sales()->count(),
                'lost_sales'     => $secondary->lostSales()->count(),
                'whatsapp'       => WhatsappConversation::withoutGlobalScope('tenant')
                                        ->where('lead_id', $secondary->id)->count(),
                'instagram'      => InstagramConversation::withoutGlobalScope('tenant')
                                        ->where('lead_id', $secondary->id)->count(),
                'website'        => WebsiteConversation::withoutGlobalScope('tenant')
                                        ->where('lead_id', $secondary->id)->count(),
                'custom_fields'  => $secondary->customFieldValues()->count(),
                'score_logs'     => $secondary->scoreLogs()->count(),
                'sequences'      => LeadSequence::where('lead_id', $secondary->id)->count(),
                'scheduled_msgs' => ScheduledMessage::withoutGlobalScope('tenant')
                                        ->where('lead_id', $secondary->id)->count(),
            ],
        ];
    }
}
