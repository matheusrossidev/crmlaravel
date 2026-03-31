<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\CustomFieldDefinition;
use App\Services\PlanLimitChecker;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WebsiteConversation;
use App\Models\WhatsappConversation;
use App\Models\WhatsappTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotFlowController extends Controller
{
    public function index(): View
    {
        $flows = ChatbotFlow::withCount(['conversations', 'websiteConversations'])->orderByDesc('created_at')->get();
        return view('tenant.chatbot.index', compact('flows'));
    }

    public function create(): View
    {
        return view('tenant.chatbot.form', ['flow' => new ChatbotFlow()]);
    }

    public function onboarding(): View
    {
        return view('tenant.chatbot.onboarding');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $limitMsg = PlanLimitChecker::check('chatbot_flows');
        if ($limitMsg) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
            }
            return redirect()->route('chatbot.flows.index')->withErrors(['limit' => $limitMsg]);
        }

        $data = $this->validatedFlow($request);
        if (($data['channel'] ?? '') === 'website') {
            $data['website_token'] = Str::uuid()->toString();
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Accept steps and variables from onboarding wizard (template pre-loaded)
        if ($request->has('steps') && is_string($request->input('steps'))) {
            $steps = json_decode($request->input('steps'), true);
            if (is_array($steps)) {
                $data['steps'] = $steps;
            }
        }
        if ($request->has('template_variables') && is_string($request->input('template_variables'))) {
            $vars = json_decode($request->input('template_variables'), true);
            if (is_array($vars)) {
                $data['variables'] = $vars;
            }
        }

        $flow = ChatbotFlow::create($data);

        // Se marcou catch-all, desativar em outros flows do tenant
        if ($flow->is_catch_all) {
            ChatbotFlow::where('id', '!=', $flow->id)
                ->where('is_catch_all', true)
                ->update(['is_catch_all' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'      => true,
                'redirect_url' => route('chatbot.flows.edit', $flow),
            ]);
        }

        return redirect()->route('chatbot.flows.edit', $flow)->with('success', 'Fluxo criado! Agora adicione os nós.');
    }

    public function edit(ChatbotFlow $flow, Request $request): View
    {
        // Settings mode: render the flow settings form instead of the node builder
        if ($request->query('settings')) {
            return view('tenant.chatbot.form', compact('flow'));
        }

        // Garantir website_token para poder testar o widget
        if (! $flow->website_token) {
            $flow->update(['website_token' => Str::uuid()->toString()]);
            $flow->refresh();
        }

        $tags = WhatsappTag::orderBy('name')->pluck('name')->all();

        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $pipelines = Pipeline::with('stages:id,pipeline_id,name,position')
            ->orderBy('name')
            ->get(['id', 'name']);

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

        return view('tenant.chatbot.builder', compact('flow', 'pipelines', 'tags', 'users', 'customFieldDefs'));
    }

    public function update(Request $request, ChatbotFlow $flow): RedirectResponse
    {
        $data = $this->validatedFlow($request);
        if (($data['channel'] ?? '') === 'website') {
            if (! $flow->website_token) {
                $data['website_token'] = Str::uuid()->toString();
            }
            if (! $flow->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $flow->id);
            }
        }
        $flow->update($data);

        // Se marcou catch-all, desativar em outros flows do tenant
        if ($flow->is_catch_all) {
            ChatbotFlow::where('id', '!=', $flow->id)
                ->where('is_catch_all', true)
                ->update(['is_catch_all' => false]);
        }

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
     * Salva o fluxo completo como JSON puro na coluna `steps`.
     */
    public function saveGraph(Request $request, ChatbotFlow $flow): JsonResponse
    {
        $validated = $request->validate([
            'steps'                   => 'required|string',
            'variables'               => 'nullable|array',
            'name'                    => 'nullable|string|max:100',
            'trigger_type'            => 'nullable|in:keyword,instagram_comment',
            'trigger_media_id'        => 'nullable|string|max:191',
            'trigger_media_thumbnail' => 'nullable|string',
            'trigger_media_caption'   => 'nullable|string',
            'trigger_reply_comment'   => 'nullable|string|max:2200',
        ]);

        $steps = json_decode($validated['steps'], true);

        if (! is_array($steps)) {
            return response()->json(['success' => false, 'message' => 'JSON inválido'], 422);
        }

        $updateData = [
            'steps'     => $steps,
            'variables' => $validated['variables'] ?? [],
        ];

        if (! empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        // Trigger type fields (Instagram comment trigger)
        if (isset($validated['trigger_type'])) {
            $updateData['trigger_type']            = $validated['trigger_type'];
            $updateData['trigger_media_id']        = $validated['trigger_media_id'] ?? null;
            $updateData['trigger_media_thumbnail'] = $validated['trigger_media_thumbnail'] ?? null;
            $updateData['trigger_media_caption']    = $validated['trigger_media_caption'] ?? null;
            $updateData['trigger_reply_comment']    = $validated['trigger_reply_comment'] ?? null;
        }

        $flow->update($updateData);

        // ── Sincronizar steps JSON → tabelas chatbot_flow_nodes / edges ──
        $this->syncNodesToDatabase($flow, $steps);

        Log::info('Chatbot: fluxo salvo', ['flow_id' => $flow->id]);

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
     * Resultados do fluxo — tabela com respostas de cada conversa (estilo Typebot).
     */
    public function results(ChatbotFlow $flow): View
    {
        // Extrair colunas dinâmicas do flow (variáveis com save_to)
        $variableKeys = $this->extractVariableKeys($flow);

        if ($flow->channel === 'website') {
            $conversations = WebsiteConversation::where('flow_id', $flow->id)
                ->whereNotNull('last_message_at')
                ->where(function ($q) {
                    $q->whereNotNull('contact_name')
                      ->orWhereNotNull('contact_email')
                      ->orWhereNotNull('contact_phone')
                      ->orWhereNotNull('lead_id');
                })
                ->orderByDesc('started_at')
                ->get();

            $rows = $conversations->map(function (WebsiteConversation $c) use ($variableKeys) {
                $vars = $c->chatbot_variables ?? [];
                $row = [
                    'id'         => $c->id,
                    'date'       => $c->started_at?->format('d/m/Y H:i'),
                    'name'       => $c->contact_name,
                    'email'      => $c->contact_email,
                    'phone'      => $c->contact_phone,
                    'status'     => $c->status,
                    'lead_id'    => $c->lead_id,
                    'utm_source'   => $c->utm_source,
                    'utm_medium'   => $c->utm_medium,
                    'utm_campaign' => $c->utm_campaign,
                    'utm_content'  => $c->utm_content,
                    'utm_term'     => $c->utm_term,
                    'variables'  => [],
                ];
                foreach ($variableKeys as $key) {
                    $row['variables'][$key] = $vars[$key] ?? '';
                }
                return $row;
            });

            $fixedColumns = ['Data', 'Nome', 'Email', 'Telefone', 'UTM Source', 'UTM Medium', 'UTM Campaign'];
        } else {
            // WhatsApp / Instagram
            $conversations = WhatsappConversation::where('chatbot_flow_id', $flow->id)
                ->orderByDesc('last_message_at')
                ->get();

            $rows = $conversations->map(function (WhatsappConversation $c) use ($variableKeys) {
                $vars = $c->chatbot_variables ?? [];
                $row = [
                    'id'     => $c->id,
                    'date'   => $c->last_message_at?->format('d/m/Y H:i') ?? $c->created_at?->format('d/m/Y H:i'),
                    'name'   => $c->contact_name,
                    'phone'  => $c->phone,
                    'status' => $c->status,
                    'lead_id' => null,
                    'variables' => [],
                ];
                foreach ($variableKeys as $key) {
                    $row['variables'][$key] = $vars[$key] ?? '';
                }
                return $row;
            });

            $fixedColumns = ['Data', 'Nome', 'Telefone'];
        }

        return view('tenant.chatbot.results', [
            'flow'          => $flow,
            'rows'          => $rows,
            'variableKeys'  => $variableKeys,
            'fixedColumns'  => $fixedColumns,
            'totalCount'    => $rows->count(),
        ]);
    }

    /**
     * Extrai as keys save_to dos nós input do flow steps.
     */
    private function extractVariableKeys(ChatbotFlow $flow): array
    {
        $keys = [];
        $steps = $flow->steps ?? [];
        $this->walkSteps($steps, $keys);
        return array_unique($keys);
    }

    private function walkSteps(array $steps, array &$keys): void
    {
        foreach ($steps as $step) {
            if (!is_array($step)) continue;

            if (($step['type'] ?? '') === 'input' && !empty($step['config']['save_to'])) {
                $keys[] = $step['config']['save_to'];
            }

            // Recurse into branches
            foreach (['branches', 'children', 'default_branch'] as $child) {
                if (isset($step[$child]) && is_array($step[$child])) {
                    $this->walkSteps($step[$child], $keys);
                }
            }

            // Some flows nest steps inside branch items
            if (isset($step['steps']) && is_array($step['steps'])) {
                $this->walkSteps($step['steps'], $keys);
            }
        }
    }

    // ── Sync steps JSON → DB tables ────────────────────────────────────────

    /**
     * Converte o array flat de steps em registros nas tabelas chatbot_flow_nodes e chatbot_flow_edges.
     * O ProcessChatbotStep lê dessas tabelas para executar o fluxo.
     */
    private function syncNodesToDatabase(ChatbotFlow $flow, array $steps): void
    {
        ChatbotFlowNode::withoutGlobalScope('tenant')->where('flow_id', $flow->id)->delete();
        ChatbotFlowEdge::withoutGlobalScope('tenant')->where('flow_id', $flow->id)->delete();

        $idMap = []; // JS temp id → DB real id
        $yPos  = 0;

        // Fase 1: criar todos os nós (incluindo sub-steps de branches)
        $this->createNodesRecursive($flow, $steps, $idMap, $yPos);

        // Fase 2: criar edges sequenciais (nó N → nó N+1) e edges de branches
        $this->createEdgesRecursive($flow, $steps, $idMap);
    }

    private function createNodesRecursive(ChatbotFlow $flow, array $steps, array &$idMap, int &$yPos): void
    {
        foreach ($steps as $jsNode) {
            $jsId = $jsNode['id'] ?? null;
            if (! $jsId) {
                continue;
            }

            $config = $jsNode['config'] ?? [];
            if (is_array($jsNode['branches'] ?? null)) {
                $config['branches'] = $jsNode['branches'];
            }
            if (isset($jsNode['default_branch'])) {
                $config['default_branch'] = $jsNode['default_branch'];
            }

            $node = ChatbotFlowNode::create([
                'flow_id'   => $flow->id,
                'tenant_id' => $flow->tenant_id,
                'type'      => $jsNode['type'] ?? 'message',
                'config'    => $config,
                'canvas_x'  => 0,
                'canvas_y'  => (float) $yPos,
                'is_start'  => $yPos === 0,
            ]);

            $idMap[$jsId] = $node->id;
            $yPos += 100;

            // Recurse into branch sub-steps
            foreach (($jsNode['branches'] ?? []) as $branch) {
                if (! empty($branch['steps'])) {
                    $this->createNodesRecursive($flow, $branch['steps'], $idMap, $yPos);
                }
            }
            if (! empty($jsNode['default_branch']['steps'])) {
                $this->createNodesRecursive($flow, $jsNode['default_branch']['steps'], $idMap, $yPos);
            }
        }
    }

    private function createEdgesRecursive(ChatbotFlow $flow, array $steps, array &$idMap): void
    {
        for ($i = 0; $i < count($steps); $i++) {
            $jsId     = $steps[$i]['id'] ?? null;
            $sourceId = $idMap[$jsId] ?? null;
            if (! $sourceId) {
                continue;
            }

            // Edge default: nó atual → próximo nó na sequência
            $nextJsId = $steps[$i + 1]['id'] ?? null;
            $targetId = $nextJsId ? ($idMap[$nextJsId] ?? null) : null;

            if ($targetId && ($steps[$i]['type'] ?? '') !== 'input') {
                // Para input nodes, não criar edge default (branches decidem o próximo)
                ChatbotFlowEdge::create([
                    'flow_id'        => $flow->id,
                    'tenant_id'      => $flow->tenant_id,
                    'source_node_id' => $sourceId,
                    'source_handle'  => 'default',
                    'target_node_id' => $targetId,
                ]);
            }

            // Edges de branches: cada branch com steps → primeiro nó do sub-step
            foreach (($steps[$i]['branches'] ?? []) as $bi => $branch) {
                $branchHandle = 'branch_' . $bi;
                if (! empty($branch['steps'])) {
                    $firstBranchJsId = $branch['steps'][0]['id'] ?? null;
                    $firstBranchDbId = $firstBranchJsId ? ($idMap[$firstBranchJsId] ?? null) : null;
                    if ($firstBranchDbId) {
                        ChatbotFlowEdge::create([
                            'flow_id'        => $flow->id,
                            'tenant_id'      => $flow->tenant_id,
                            'source_node_id' => $sourceId,
                            'source_handle'  => $branchHandle,
                            'target_node_id' => $firstBranchDbId,
                        ]);
                    }
                    $this->createEdgesRecursive($flow, $branch['steps'], $idMap);
                } elseif ($targetId) {
                    // Branch vazio → avança para o próximo nó na sequência principal
                    ChatbotFlowEdge::create([
                        'flow_id'        => $flow->id,
                        'tenant_id'      => $flow->tenant_id,
                        'source_node_id' => $sourceId,
                        'source_handle'  => $branchHandle,
                        'target_node_id' => $targetId,
                    ]);
                }
            }

            // Default branch
            if (! empty($steps[$i]['default_branch']['steps'])) {
                $firstDefJsId = $steps[$i]['default_branch']['steps'][0]['id'] ?? null;
                $firstDefDbId = $firstDefJsId ? ($idMap[$firstDefJsId] ?? null) : null;
                if ($firstDefDbId) {
                    ChatbotFlowEdge::create([
                        'flow_id'        => $flow->id,
                        'tenant_id'      => $flow->tenant_id,
                        'source_node_id' => $sourceId,
                        'source_handle'  => 'default',
                        'target_node_id' => $firstDefDbId,
                    ]);
                }
                $this->createEdgesRecursive($flow, $steps[$i]['default_branch']['steps'], $idMap);
            } elseif (($steps[$i]['type'] ?? '') === 'input' && $targetId) {
                // Input sem default_branch steps → default vai para próximo nó
                ChatbotFlowEdge::create([
                    'flow_id'        => $flow->id,
                    'tenant_id'      => $flow->tenant_id,
                    'source_node_id' => $sourceId,
                    'source_handle'  => 'default',
                    'target_node_id' => $targetId,
                ]);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validatedFlow(Request $request): array
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:100',
            'channel'                 => 'required|in:whatsapp,instagram,website',
            'description'             => 'nullable|string|max:1000',
            'is_active'               => 'boolean',
            'is_catch_all'            => 'boolean',
            'trigger_keywords'        => 'nullable|string',
            'trigger_type'            => 'nullable|in:keyword,instagram_comment',
            'trigger_media_id'        => 'nullable|string|max:191',
            'trigger_media_thumbnail' => 'nullable|string',
            'trigger_media_caption'   => 'nullable|string',
            'trigger_reply_comment'   => 'nullable|string|max:2200',
            'variables'               => 'nullable|string',
            'bot_name'                => 'nullable|string|max:100',
            'bot_avatar'              => 'nullable|string|max:500',
            'welcome_message'         => 'nullable|string|max:500',
            'widget_type'             => 'nullable|in:bubble,inline',
            'widget_color'            => 'nullable|string|max:10',
            'slug'                    => 'nullable|string|max:191',
        ]);

        $data['trigger_type'] = $data['trigger_type'] ?? 'keyword';

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

        // Sanitize slug
        if (! empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        return $data;
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'chatbot';
        }

        $slug = $base;
        $i = 2;
        $tenantId = auth()->user()->tenant_id;

        while (
            ChatbotFlow::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
