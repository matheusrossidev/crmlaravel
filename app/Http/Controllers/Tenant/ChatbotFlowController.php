<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\Pipeline;
use App\Models\WhatsappTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ChatbotFlowController extends Controller
{
    public function index(): View
    {
        $flows = ChatbotFlow::orderByDesc('created_at')->get();
        return view('tenant.chatbot.index', compact('flows'));
    }

    public function create(): View
    {
        return view('tenant.chatbot.form', ['flow' => new ChatbotFlow()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedFlow($request);
        $flow = ChatbotFlow::create($data);
        return redirect()->route('chatbot.flows.edit', $flow)->with('success', 'Fluxo criado! Agora adicione os nós.');
    }

    public function edit(ChatbotFlow $flow): View
    {
        $nodes = $flow->nodes()->get()->map(fn ($n) => [
            'id'       => (string) $n->id,
            'type'     => $n->type,
            'position' => ['x' => $n->canvas_x, 'y' => $n->canvas_y],
            'data'     => array_merge(['label' => $n->label ?? ''], $n->config ?? []),
        ]);

        $edges = ChatbotFlowEdge::where('flow_id', $flow->id)->get()->map(fn ($e) => [
            'id'           => (string) $e->id,
            'source'       => (string) $e->source_node_id,
            'sourceHandle' => $e->source_handle,
            'target'       => (string) $e->target_node_id,
        ]);

        $tags = WhatsappTag::orderBy('name')->pluck('name')->all();

        $builderData = [
            'flow'         => [
                'id'               => $flow->id,
                'name'             => $flow->name,
                'is_active'        => $flow->is_active,
                'variables'        => $flow->variables ?? [],
                'trigger_keywords' => $flow->trigger_keywords ?? [],
            ],
            'nodes'        => $nodes->values()->all(),
            'edges'        => $edges->values()->all(),
            'saveUrl'      => route('chatbot.flows.graph', $flow),
            'pipelinesUrl' => route('chatbot.flows.pipelines'),
            'uploadUrl'    => route('chatbot.flows.upload-image'),
            'toggleUrl'    => route('chatbot.flows.toggle', $flow),
            'csrfToken'    => csrf_token(),
            'tags'         => $tags,
        ];

        return view('tenant.chatbot.edit', compact('flow', 'builderData'));
    }

    public function update(Request $request, ChatbotFlow $flow): RedirectResponse
    {
        $data = $this->validatedFlow($request);
        $flow->update($data);
        return redirect()->route('chatbot.flows.edit', $flow)->with('success', 'Fluxo atualizado.');
    }

    public function destroy(ChatbotFlow $flow): RedirectResponse
    {
        $flow->delete();
        return redirect()->route('chatbot.flows.index')->with('success', 'Fluxo excluído.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:10240']); // 10 MB

        $path = $request->file('image')->store('chatbot-images', 'public');

        return response()->json(['url' => asset('storage/' . $path)]);
    }

    public function toggle(ChatbotFlow $flow): JsonResponse
    {
        $flow->update(['is_active' => ! $flow->is_active]);
        return response()->json(['is_active' => $flow->is_active]);
    }

    /**
     * Salva o estado completo do grafo React Flow (upsert atômico).
     * Recebe: { nodes: [{id, type, position, data}], edges: [{id, source, sourceHandle, target}] }
     */
    public function saveGraph(Request $request, ChatbotFlow $flow): JsonResponse
    {
        $validated = $request->validate([
            'nodes'              => 'required|array',
            'nodes.*.id'         => 'required|string',
            'nodes.*.type'       => 'required|string|in:message,input,condition,action,end',
            'nodes.*.position.x' => 'required|numeric',
            'nodes.*.position.y' => 'required|numeric',
            'nodes.*.data'       => 'required|array',
            'edges'              => 'present|array',
            'edges.*.source'     => 'required|string',
            'edges.*.target'     => 'required|string',
            'trigger_keywords'   => 'nullable|array',
            'trigger_keywords.*' => 'string|max:100',
        ]);

        DB::transaction(function () use ($validated, $flow) {
            $tenantId = auth()->user()->tenant_id;

            // ── Processar nós ─────────────────────────────────────────────────
            // Mapeamento de IDs temporários (React Flow usa strings como "node-1") para IDs do banco
            $idMap = []; // 'old_id' => new_db_id

            // IDs que vieram do frontend (alguns são IDs do banco, outros são temporários)
            $incomingIds = collect($validated['nodes'])->pluck('id')->all();

            // Deletar nós que não estão mais no grafo
            ChatbotFlowNode::withoutGlobalScope('tenant')
                ->where('flow_id', $flow->id)
                ->whereNotIn('id', array_filter($incomingIds, 'is_numeric'))
                ->delete();

            foreach ($validated['nodes'] as $nodeData) {
                $nodeId   = $nodeData['id'];
                $data     = $nodeData['data'];
                $label    = $data['label'] ?? null;
                // Config = data sem o campo label
                $config   = array_diff_key($data, ['label' => '']);

                $attrs = [
                    'flow_id'   => $flow->id,
                    'tenant_id' => $tenantId,
                    'type'      => $nodeData['type'],
                    'label'     => $label,
                    'config'    => $config,
                    'canvas_x'  => $nodeData['position']['x'],
                    'canvas_y'  => $nodeData['position']['y'],
                ];

                if (is_numeric($nodeId)) {
                    // Nó existente — update
                    ChatbotFlowNode::withoutGlobalScope('tenant')
                        ->where('id', (int) $nodeId)
                        ->where('flow_id', $flow->id)
                        ->update($attrs);
                    $idMap[$nodeId] = (int) $nodeId;
                } else {
                    // Nó novo — create
                    $node           = ChatbotFlowNode::withoutGlobalScope('tenant')->create($attrs);
                    $idMap[$nodeId] = $node->id;
                }
            }

            // ── Processar edges ───────────────────────────────────────────────
            // Deletar todas as edges existentes e recriar (simples e correto)
            ChatbotFlowEdge::withoutGlobalScope('tenant')
                ->where('flow_id', $flow->id)
                ->delete();

            foreach ($validated['edges'] as $edgeData) {
                $sourceNodeId = $idMap[$edgeData['source']] ?? (int) $edgeData['source'];
                $targetNodeId = $idMap[$edgeData['target']] ?? (int) $edgeData['target'];

                if (! $sourceNodeId || ! $targetNodeId) {
                    continue;
                }

                ChatbotFlowEdge::withoutGlobalScope('tenant')->updateOrCreate(
                    [
                        'flow_id'       => $flow->id,
                        'source_node_id' => $sourceNodeId,
                        'source_handle' => $edgeData['sourceHandle'] ?? 'default',
                    ],
                    [
                        'tenant_id'      => $tenantId,
                        'target_node_id' => $targetNodeId,
                    ],
                );
            }
        });

        // Atualizar trigger_keywords do flow se enviado pelo builder
        if (array_key_exists('trigger_keywords', $validated)) {
            $keywords = array_values(array_filter(array_map('trim', $validated['trigger_keywords'] ?? [])));
            $flow->update(['trigger_keywords' => $keywords ?: null]);
        }

        Log::info('Chatbot: grafo salvo', ['flow_id' => $flow->id]);

        return response()->json(['success' => true]);
    }

    /**
     * Retorna pipelines e stages do tenant (para o dropdown change_stage na UI).
     */
    public function getPipelines(): JsonResponse
    {
        $pipelines = Pipeline::with('stages:id,pipeline_id,name,position')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'stages' => $p->stages->sortBy('position')->values()->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                ]),
            ]);

        return response()->json($pipelines);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validatedFlow(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'boolean',
            'trigger_keywords' => 'nullable|string',
            'variables'        => 'nullable|string',
        ]);

        // Converter campos JSON string → array
        if (isset($data['trigger_keywords']) && is_string($data['trigger_keywords'])) {
            $keywords = array_map('trim', explode(',', $data['trigger_keywords']));
            $data['trigger_keywords'] = array_values(array_filter($keywords));
        }

        if (isset($data['variables']) && is_string($data['variables'])) {
            $vars = [];
            foreach (array_filter(array_map('trim', explode(',', $data['variables']))) as $v) {
                $vars[] = ['name' => $v, 'default' => ''];
            }
            $data['variables'] = $vars;
        }

        return $data;
    }
}
