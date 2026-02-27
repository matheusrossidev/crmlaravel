<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LostSaleReason;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function index(): View
    {
        $pipelines = Pipeline::with(['stages' => fn($q) => $q->orderBy('position')])->orderBy('sort_order')->get();
        $reasons   = LostSaleReason::orderBy('sort_order')->get();

        return view('tenant.settings.pipelines', compact('pipelines', 'reasons'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'required|string|max:100',
            'color'                      => 'required|string|max:20',
            'auto_create_lead'           => 'nullable|boolean',
            'auto_create_from_whatsapp'  => 'nullable|boolean',
            'auto_create_from_instagram' => 'nullable|boolean',
        ]);

        $data['sort_order']                  = Pipeline::max('sort_order') + 1;
        $data['is_default']                  = false;
        $data['auto_create_lead']            = $data['auto_create_lead'] ?? true;
        $data['auto_create_from_whatsapp']   = $data['auto_create_from_whatsapp'] ?? true;
        $data['auto_create_from_instagram']  = $data['auto_create_from_instagram'] ?? true;

        $pipeline = Pipeline::create($data);
        $pipeline->load('stages');

        return response()->json(['success' => true, 'pipeline' => $pipeline]);
    }

    public function update(Request $request, Pipeline $pipeline): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'required|string|max:100',
            'color'                      => 'required|string|max:20',
            'is_default'                 => 'boolean',
            'auto_create_lead'           => 'nullable|boolean',
            'auto_create_from_whatsapp'  => 'nullable|boolean',
            'auto_create_from_instagram' => 'nullable|boolean',
        ]);

        if (!empty($data['is_default'])) {
            Pipeline::where('id', '!=', $pipeline->id)->update(['is_default' => false]);
        }

        $pipeline->update($data);

        return response()->json(['success' => true, 'pipeline' => $pipeline]);
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        if ($pipeline->leads()->exists()) {
            return response()->json(['success' => false, 'message' => 'Este funil possui leads. Mova-os antes de excluir.'], 422);
        }

        $pipeline->stages()->delete();
        $pipeline->delete();

        return response()->json(['success' => true]);
    }

    public function storeStage(Request $request, Pipeline $pipeline): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'color'   => 'required|string|max:20',
            'is_won'  => 'boolean',
            'is_lost' => 'boolean',
        ]);

        $data['pipeline_id'] = $pipeline->id;
        $data['position']    = $pipeline->stages()->max('position') + 1;

        $stage = PipelineStage::create($data);

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function updateStage(Request $request, Pipeline $pipeline, PipelineStage $stage): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'color'   => 'required|string|max:20',
            'is_won'  => 'boolean',
            'is_lost' => 'boolean',
        ]);

        $stage->update($data);

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function destroyStage(Pipeline $pipeline, PipelineStage $stage): JsonResponse
    {
        if ($stage->leads()->exists()) {
            return response()->json(['success' => false, 'message' => 'Esta etapa possui leads. Mova-os antes de excluir.'], 422);
        }

        $stage->delete();

        return response()->json(['success' => true]);
    }

    public function reorderStages(Request $request, Pipeline $pipeline): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $position => $stageId) {
            PipelineStage::where('id', $stageId)
                ->where('pipeline_id', $pipeline->id)
                ->update(['position' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }
}
