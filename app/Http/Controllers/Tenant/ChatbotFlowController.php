<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\CustomFieldDefinition;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WhatsappTag;
use App\Services\ChatbotVariableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotFlowController extends Controller
{
    public function index(): View
    {
        $flows = ChatbotFlow::withCount('conversations')->orderByDesc('created_at')->get();
        return view('tenant.chatbot.index', compact('flows'));
    }

    public function create(): View
    {
        return view('tenant.chatbot.form', ['flow' => new ChatbotFlow()]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Limite de 1 fluxo de chatbot por tenant
        if (ChatbotFlow::exists()) {
            return redirect()->route('chatbot.flows.index')
                ->withErrors(['limit' => 'Cada conta pode ter apenas 1 fluxo de chatbot. Edite o fluxo existente.']);
        }

        $data = $this->validatedFlow($request);
        if (($data['channel'] ?? '') === 'website') {
            $data['website_token'] = Str::uuid()->toString();
        }
        $flow = ChatbotFlow::create($data);
        return redirect()->route('chatbot.flows.edit', $flow)->with('success', 'Fluxo criado! Agora adicione os nós.');
    }

    public function edit(ChatbotFlow $flow, Request $request): View
    {
        // Settings mode: render the flow settings form instead of the node builder
        if ($request->query('settings')) {
            return view('tenant.chatbot.form', compact('flow'));
        }

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

        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $customFieldDefs = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label', 'field_type', 'options_json'])
            ->map(fn ($d) => [
                'name'       => $d->name,
                'label'      => $d->label,
                'field_type' => $d->field_type,
                'options'    => $d->options_json ?? [],
            ])
            ->values()
            ->all();

        $builderData = [
            'flow'            => [
                'id'               => $flow->id,
                'name'             => $flow->name,
                'is_active'        => $flow->is_active,
                'variables'        => $flow->variables ?? [],
                'trigger_keywords' => $flow->trigger_keywords ?? [],
            ],
            'nodes'           => $nodes->values()->all(),
            'edges'           => $edges->values()->all(),
            'saveUrl'         => route('chatbot.flows.graph', $flow),
            'pipelinesUrl'    => route('chatbot.flows.pipelines'),
            'uploadUrl'       => route('chatbot.flows.upload-image'),
            'toggleUrl'       => route('chatbot.flows.toggle', $flow),
            'csrfToken'       => csrf_token(),
            'tags'            => $tags,
            'users'           => $users,
            'customFieldDefs' => $customFieldDefs,
        ];

        return view('tenant.chatbot.edit', compact('flow', 'builderData'));
    }

    public function update(Request $request, ChatbotFlow $flow): RedirectResponse
    {
        $data = $this->validatedFlow($request);
        if (($data['channel'] ?? '') === 'website' && ! $flow->website_token) {
            $data['website_token'] = Str::uuid()->toString();
        }
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
            'nodes.*.type'       => 'required|string|in:message,input,condition,action,delay,end',
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

    /**
     * Simulação do fluxo sem enviar mensagens reais (test chat).
     * Request: { message?: string, state: { node_id?: int, vars?: object } }
     */
    public function testStep(Request $request, ChatbotFlow $flow): JsonResponse
    {
        $request->validate([
            'message'       => 'nullable|string|max:1000',
            'state'         => 'nullable|array',
            'state.node_id' => 'nullable|integer',
            'state.vars'    => 'nullable|array',
        ]);

        $inbound       = trim((string) $request->input('message', ''));
        $state         = $request->input('state', []);
        $waitingNodeId = isset($state['node_id']) ? (int) $state['node_id'] : null;

        // Variáveis de sistema com valores de exemplo para o teste
        $vars = array_merge([
            '$contact_name'         => 'Visitante',
            '$contact_phone'        => '5511999999999',
            '$lead_exists'          => 'false',
            '$lead_stage_name'      => '',
            '$lead_stage_id'        => '',
            '$lead_source'          => '',
            '$lead_tags'            => '',
            '$conversations_count'  => '1',
            '$is_returning_contact' => 'false',
            '$messages_count'       => '1',
        ], (array) ($state['vars'] ?? []));

        [$messages, $newNodeId, $newVars, $done] = $this->simulateFlow($flow, $waitingNodeId, $inbound, $vars);

        return response()->json([
            'messages' => $messages,
            'state'    => ['node_id' => $newNodeId, 'vars' => $newVars],
            'done'     => $done,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validatedFlow(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'channel'          => 'required|in:whatsapp,instagram,website',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'boolean',
            'trigger_keywords' => 'nullable|string',
            'variables'        => 'nullable|string',
            'bot_name'         => 'nullable|string|max:100',
            'bot_avatar'       => 'nullable|string|max:500',  // accepts relative paths (/images/...) or full URLs
            'welcome_message'  => 'nullable|string|max:500',
            'widget_type'      => 'nullable|in:bubble,inline',
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

    /**
     * Percorre o fluxo em modo dry-run, coletando mensagens sem efeitos colaterais reais.
     *
     * @return array{0: list<array>, 1: int|null, 2: array, 3: bool}
     *              [messages, nextWaitingNodeId, vars, done]
     */
    private function simulateFlow(ChatbotFlow $flow, ?int $waitingNodeId, string $inbound, array $vars): array
    {
        $messages = [];
        $done     = false;
        $newNode  = null;
        $maxIter  = 30;
        $iter     = 0;

        $nodes = ChatbotFlowNode::where('flow_id', $flow->id)->get()->keyBy('id');
        $edges = ChatbotFlowEdge::where('flow_id', $flow->id)->get();

        $edge = fn (int $src, string $handle): ?int => $edges
            ->where('source_node_id', $src)
            ->where('source_handle', $handle)
            ->first()?->target_node_id;

        // ── Resolver próximo nó a partir do estado ────────────────────────────
        if ($waitingNodeId) {
            $waiting = $nodes->get($waitingNodeId);
            if (! $waiting) {
                return [[], null, $vars, true];
            }

            $nextId = null;
            if ($waiting->type === 'input') {
                $saveTo = $waiting->config['save_to'] ?? null;
                if ($saveTo && ! str_starts_with($saveTo, '$')) {
                    $vars[$saveTo] = $inbound;
                }
                foreach ($waiting->config['branches'] ?? [] as $branch) {
                    $kws = array_map('strtolower', (array) ($branch['keywords'] ?? []));
                    if (in_array(strtolower($inbound), $kws, true)) {
                        $nextId = $edge($waitingNodeId, $branch['handle'] ?? '');
                        break;
                    }
                }
                $nextId ??= $edge($waitingNodeId, 'default');
            } else {
                $nextId = $edge($waitingNodeId, 'default');
            }

            $current = $nextId ? $nodes->get($nextId) : null;
        } else {
            $targetIds = $edges->pluck('target_node_id')->toArray();
            $current   = $nodes->filter(fn ($n) => ! in_array($n->id, $targetIds))->sortBy('canvas_y')->first();
        }

        // ── Loop principal ────────────────────────────────────────────────────
        while ($current && $iter < $maxIter) {
            $iter++;
            $cfg = $current->config ?? [];

            switch ($current->type) {
                case 'message':
                case 'input':
                    $text  = ChatbotVariableService::interpolate((string) ($cfg['text'] ?? ''), $vars);
                    $img   = (string) ($cfg['image_url'] ?? '');
                    if ($img !== '') {
                        $messages[] = ['type' => 'image', 'url' => $img, 'caption' => $text];
                    } elseif ($text !== '') {
                        $messages[] = ['type' => 'text', 'content' => $text];
                    }
                    if ($current->type === 'input') {
                        $newNode = $current->id;
                        $current = null;
                        break 2;
                    }
                    $current = ($nid = $edge($current->id, 'default')) ? $nodes->get($nid) : null;
                    break;

                case 'condition':
                    $varVal     = strtolower((string) ($vars[$cfg['variable'] ?? ''] ?? ''));
                    $nextId     = null;
                    foreach ($cfg['conditions'] ?? [] as $cond) {
                        $val     = strtolower((string) ($cond['value'] ?? ''));
                        $matched = match ($cond['operator'] ?? 'equals') {
                            'equals'      => $varVal === $val,
                            'not_equals'  => $varVal !== $val,
                            'contains'    => str_contains($varVal, $val),
                            'starts_with' => str_starts_with($varVal, $val),
                            'ends_with'   => str_ends_with($varVal, $val),
                            'gt'          => is_numeric($varVal) && is_numeric($val) && (float) $varVal > (float) $val,
                            'lt'          => is_numeric($varVal) && is_numeric($val) && (float) $varVal < (float) $val,
                            default       => false,
                        };
                        if ($matched) {
                            $nextId = $edge($current->id, $cond['handle'] ?? 'default');
                            break;
                        }
                    }
                    $nextId  ??= $edge($current->id, 'default');
                    $current   = $nextId ? $nodes->get($nextId) : null;
                    break;

                case 'action':
                    $type       = $cfg['type'] ?? '';
                    $label      = match ($type) {
                        'change_stage'       => '📋 Etapa alterada',
                        'add_tag'            => '🏷️ Tag adicionada: ' . ($cfg['value'] ?? ''),
                        'remove_tag'         => '🏷️ Tag removida: ' . ($cfg['value'] ?? ''),
                        'assign_human'       => '👤 Transferido para atendente humano',
                        'close_conversation' => '🔒 Conversa encerrada',
                        'save_variable'      => '💾 Variável salva: ' . ($cfg['variable'] ?? ''),
                        'send_webhook'       => '🔗 Webhook: ' . ($cfg['url'] ?? ''),
                        'set_custom_field'   => '📝 Campo preenchido: ' . ($cfg['field_label'] ?? ($cfg['field_name'] ?? '')),
                        default              => '⚙️ Ação: ' . $type,
                    };
                    $messages[] = ['type' => 'system', 'content' => $label];
                    if ($type === 'save_variable') {
                        $vn = (string) ($cfg['variable'] ?? '');
                        if ($vn && ! str_starts_with($vn, '$')) {
                            $vars[$vn] = ChatbotVariableService::interpolate((string) ($cfg['value'] ?? ''), $vars);
                        }
                    }
                    $current = ($nid = $edge($current->id, 'default')) ? $nodes->get($nid) : null;
                    break;

                case 'delay':
                    $secs       = (int) ($cfg['seconds'] ?? 3);
                    $messages[] = ['type' => 'system', 'content' => "⏱️ Aguarda {$secs}s"];
                    $current    = ($nid = $edge($current->id, 'default')) ? $nodes->get($nid) : null;
                    break;

                case 'end':
                    $text = ChatbotVariableService::interpolate((string) ($cfg['text'] ?? ''), $vars);
                    if ($text !== '') {
                        $messages[] = ['type' => 'text', 'content' => $text];
                    }
                    $done    = true;
                    $current = null;
                    break;

                default:
                    $current = ($nid = $edge($current->id, 'default')) ? $nodes->get($nid) : null;
            }
        }

        return [$messages, $newNode, $vars, $done];
    }
}
