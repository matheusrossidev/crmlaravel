<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\CustomFieldDefinition;
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
        $tenant = auth()->user()->tenant;
        $max = $tenant->max_chatbot_flows ?? 0;
        if ($max > 0 && ChatbotFlow::count() >= $max) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => "Limite de {$max} fluxo(s) de chatbot atingido."], 422);
            }
            return redirect()->route('chatbot.flows.index')
                ->withErrors(['limit' => "Limite de {$max} fluxo(s) de chatbot atingido. Atualize seu plano para criar mais."]);
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
            'steps'     => 'required|string',
            'variables' => 'nullable|array',
            'name'      => 'nullable|string|max:100',
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

        $flow->update($updateData);

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
            'bot_avatar'       => 'nullable|string|max:500',
            'welcome_message'  => 'nullable|string|max:500',
            'widget_type'      => 'nullable|in:bubble,inline',
            'widget_color'     => 'nullable|string|max:10',
            'slug'             => 'nullable|string|max:191',
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
