<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\CustomFieldDefinition;
use App\Models\Pipeline;
use App\Models\User;
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

}
