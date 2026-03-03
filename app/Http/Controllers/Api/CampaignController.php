<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // ── GET /api/v1/campaigns ─────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $campaigns = Campaign::withCount('leads')
            ->orderBy('name')
            ->get()
            ->map(fn (Campaign $c) => $this->formatCampaign($c));

        return response()->json(['success' => true, 'campaigns' => $campaigns]);
    }

    // ── POST /api/v1/campaigns ────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:500',
            'status'          => 'nullable|in:active,paused,archived',
            'type'            => 'nullable|in:manual,facebook,google',
            'campaign_type'   => 'nullable|string|max:50',
            'objective'       => 'nullable|string|max:100',
            'budget_daily'    => 'nullable|numeric|min:0',
            'budget_lifetime' => 'nullable|numeric|min:0',
            'destination_url' => 'nullable|string|max:2000',
            'utm_source'      => 'nullable|string|max:100',
            'utm_medium'      => 'nullable|string|max:100',
            'utm_campaign'    => 'nullable|string|max:200',
            'utm_term'        => 'nullable|string|max:200',
            'utm_content'     => 'nullable|string|max:200',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $data['type']   = $data['type']   ?? 'manual';

        $campaign = Campaign::create($data);

        return response()->json([
            'success'  => true,
            'campaign' => $this->formatCampaign($campaign),
        ], 201);
    }

    // ── PUT /api/v1/campaigns/{campaign} ──────────────────────────────────────
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'sometimes|required|string|max:500',
            'status'          => 'sometimes|in:active,paused,archived',
            'type'            => 'sometimes|in:manual,facebook,google',
            'campaign_type'   => 'nullable|string|max:50',
            'objective'       => 'nullable|string|max:100',
            'budget_daily'    => 'nullable|numeric|min:0',
            'budget_lifetime' => 'nullable|numeric|min:0',
            'destination_url' => 'nullable|string|max:2000',
            'utm_source'      => 'nullable|string|max:100',
            'utm_medium'      => 'nullable|string|max:100',
            'utm_campaign'    => 'nullable|string|max:200',
            'utm_term'        => 'nullable|string|max:200',
            'utm_content'     => 'nullable|string|max:200',
        ]);

        $campaign->update($data);

        return response()->json([
            'success'  => true,
            'campaign' => $this->formatCampaign($campaign->fresh()),
        ]);
    }

    // ── DELETE /api/v1/campaigns/{campaign} ───────────────────────────────────
    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json(['success' => true]);
    }

    private function formatCampaign(Campaign $c): array
    {
        return [
            'id'              => $c->id,
            'name'            => $c->name,
            'status'          => $c->status,
            'type'            => $c->type ?? 'manual',
            'platform'        => $c->platform,
            'campaign_type'   => $c->campaign_type,
            'objective'       => $c->objective,
            'budget_daily'    => $c->budget_daily,
            'budget_lifetime' => $c->budget_lifetime,
            'destination_url' => $c->destination_url,
            'utm_source'      => $c->utm_source,
            'utm_medium'      => $c->utm_medium,
            'utm_campaign'    => $c->utm_campaign,
            'utm_term'        => $c->utm_term,
            'utm_content'     => $c->utm_content,
            'leads_count'     => $c->leads_count ?? null,
            'created_at'      => $c->created_at?->toISOString(),
        ];
    }
}
