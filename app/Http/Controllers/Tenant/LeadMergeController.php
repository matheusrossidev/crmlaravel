<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadDuplicate;
use App\Services\DuplicateLeadDetector;
use App\Services\LeadMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadMergeController extends Controller
{
    /** Page: duplicate review queue */
    public function duplicates(): View
    {
        $pendingCount = LeadDuplicate::where('status', 'pending')->count();

        return view('tenant.leads.duplicates', compact('pendingCount'));
    }

    /** JSON data for DataTable */
    public function duplicatesData(Request $request): JsonResponse
    {
        $query = LeadDuplicate::with(['leadA:id,name,phone,email,company,stage_id,created_at',
                                       'leadB:id,name,phone,email,company,stage_id,created_at',
                                       'leadA.stage:id,name,color',
                                       'leadB.stage:id,name,color'])
            ->where('status', $request->input('status', 'pending'))
            ->orderByDesc('score')
            ->orderByDesc('created_at');

        if ($request->filled('min_score')) {
            $query->where('score', '>=', (int) $request->input('min_score'));
        }

        $duplicates = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $duplicates,
        ]);
    }

    /** Preview what merge would do */
    public function preview(Lead $primary, Lead $secondary): JsonResponse
    {
        if ($primary->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $service = new LeadMergeService();
        $preview = $service->preview($primary, $secondary);

        return response()->json([
            'success' => true,
            'data'    => $preview,
        ]);
    }

    /** Execute merge */
    public function merge(Request $request, Lead $primary, Lead $secondary): JsonResponse
    {
        if ($primary->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        try {
            $service = new LeadMergeService();
            $merged = $service->merge($primary, $secondary);

            return response()->json([
                'success' => true,
                'message' => "Lead #{$secondary->id} mesclado com sucesso em #{$primary->id}.",
                'lead'    => $merged->only(['id', 'name', 'phone', 'email', 'company']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /** Ignore a duplicate pair */
    public function ignore(LeadDuplicate $duplicate): JsonResponse
    {
        $duplicate->update([
            'status'      => 'ignored',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Par ignorado.',
        ]);
    }

    /** Detect duplicates for a lead being created (real-time check) */
    public function detect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:191',
        ]);

        $detector = new DuplicateLeadDetector();
        $duplicates = $detector->findDuplicatesFromData($data, auth()->user()->tenant_id);

        // Only return high-confidence matches (>= 70)
        $highConfidence = $duplicates->filter(fn ($d) => $d['score'] >= 70);

        return response()->json([
            'success'          => true,
            'duplicates_found' => $highConfidence->isNotEmpty(),
            'duplicates'       => $highConfidence->map(fn ($d) => [
                'id'         => $d['lead']->id,
                'name'       => $d['lead']->name,
                'phone'      => $d['lead']->phone,
                'email'      => $d['lead']->email,
                'company'    => $d['lead']->company,
                'score'      => $d['score'],
                'created_at' => $d['lead']->created_at?->format('d/m/Y'),
                'stage'      => $d['lead']->stage?->name,
            ])->values(),
        ]);
    }
}
