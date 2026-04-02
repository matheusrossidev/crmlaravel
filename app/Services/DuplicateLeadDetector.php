<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Collection;

class DuplicateLeadDetector
{
    /**
     * Find potential duplicates for a lead within the same tenant.
     *
     * @return Collection<int, array{lead: Lead, score: int}>
     */
    public function findDuplicates(Lead $lead, ?int $tenantId = null): Collection
    {
        $tenantId = $tenantId ?? $lead->tenant_id;
        $candidates = collect();

        // 1. Phone match (60 pts)
        if ($lead->phone) {
            $normalized = $this->normalizePhone($lead->phone);
            if (strlen($normalized) >= 8) {
                $phoneCandidates = Lead::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $lead->id ?? 0)
                    ->where('status', 'active')
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->get()
                    ->filter(fn (Lead $l) => $this->phonesMatch($normalized, $this->normalizePhone($l->phone)));

                foreach ($phoneCandidates as $candidate) {
                    $candidates->put($candidate->id, $candidate);
                }
            }
        }

        // 2. Email match (50 pts)
        if ($lead->email) {
            $emailCandidates = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $lead->id ?? 0)
                ->where('status', 'active')
                ->whereRaw('LOWER(email) = ?', [strtolower($lead->email)])
                ->get();

            foreach ($emailCandidates as $candidate) {
                $candidates->put($candidate->id, $candidate);
            }
        }

        // 3. Name fuzzy (up to 30 pts) — only if phone/email didn't find anything
        if ($candidates->isEmpty() && $lead->name) {
            $nameCandidates = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $lead->id ?? 0)
                ->where('status', 'active')
                ->get(['id', 'name', 'phone', 'email', 'company', 'tenant_id',
                       'pipeline_id', 'stage_id', 'source', 'tags', 'assigned_to',
                       'created_at', 'value', 'status', 'merged_into', 'merged_at'])
                ->filter(function (Lead $l) use ($lead) {
                    similar_text(
                        strtolower(trim($lead->name)),
                        strtolower(trim($l->name)),
                        $pct
                    );
                    return $pct > 85;
                });

            foreach ($nameCandidates as $candidate) {
                $candidates->put($candidate->id, $candidate);
            }
        }

        // Score each candidate
        return $candidates->map(fn (Lead $candidate) => [
            'lead'  => $candidate,
            'score' => $this->scoreMatch($lead, $candidate),
        ])->filter(fn (array $item) => $item['score'] >= 40)
          ->sortByDesc('score')
          ->values();
    }

    /**
     * Find duplicates from raw data (before a lead is created).
     *
     * @return Collection<int, array{lead: Lead, score: int}>
     */
    public function findDuplicatesFromData(array $data, int $tenantId): Collection
    {
        $fakeLead = new Lead(array_merge($data, ['tenant_id' => $tenantId]));
        $fakeLead->id = 0;
        $fakeLead->tenant_id = $tenantId;

        return $this->findDuplicates($fakeLead, $tenantId);
    }

    /**
     * Score how likely two leads are duplicates (0-100).
     */
    public function scoreMatch(Lead $a, Lead $b): int
    {
        $score = 0;

        // Phone match (60 pts)
        if ($a->phone && $b->phone) {
            $phoneA = $this->normalizePhone($a->phone);
            $phoneB = $this->normalizePhone($b->phone);
            if ($this->phonesMatch($phoneA, $phoneB)) {
                $score += 60;
            }
        }

        // Email match (50 pts)
        if ($a->email && $b->email && strtolower($a->email) === strtolower($b->email)) {
            $score += 50;
        }

        // Name similarity (up to 30 pts)
        if ($a->name && $b->name) {
            similar_text(strtolower(trim($a->name)), strtolower(trim($b->name)), $pct);
            $score += (int) round(($pct / 100) * 30);
        }

        // Company match (20 pts)
        if ($a->company && $b->company && strtolower(trim($a->company)) === strtolower(trim($b->company))) {
            $score += 20;
        }

        return min($score, 100);
    }

    /**
     * Normalize phone: remove all non-digits.
     */
    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    /**
     * Compare two normalized phones — compare last 11 digits to ignore country code variations.
     */
    private function phonesMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        // Compare last 11 digits (ignores +55 prefix variations)
        $suffixLen = min(11, strlen($a), strlen($b));
        if ($suffixLen < 8) {
            return false;
        }

        return substr($a, -$suffixLen) === substr($b, -$suffixLen);
    }
}
