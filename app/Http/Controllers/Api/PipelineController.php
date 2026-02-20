<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LostSaleReason;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;

class PipelineController extends Controller
{
    // ── GET /api/v1/pipelines ─────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $pipelines = Pipeline::with(['stages' => fn ($q) => $q->orderBy('position')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Pipeline $p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'stages' => $p->stages->map(fn ($s) => [
                    'id'      => $s->id,
                    'name'    => $s->name,
                    'color'   => $s->color,
                    'is_won'  => (bool) $s->is_won,
                    'is_lost' => (bool) $s->is_lost,
                ]),
            ]);

        $lostReasons = LostSaleReason::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return response()->json([
            'success'      => true,
            'pipelines'    => $pipelines,
            'lost_reasons' => $lostReasons,
        ]);
    }
}
