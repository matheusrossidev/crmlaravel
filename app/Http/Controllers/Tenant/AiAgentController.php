<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $agents = AiAgent::orderByDesc('created_at')->get();

        return view('tenant.ai.agents.index', compact('agents'));
    }

    public function create(): View
    {
        return view('tenant.ai.agents.create');
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = true;
        $agent = AiAgent::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'redirect' => route('ai.agents.edit', $agent->id),
            ]);
        }

        return redirect()->route('ai.agents.index')->with('success', 'Agente criado com sucesso.');
    }

    public function edit(AiAgent $agent): View
    {
        return view('tenant.ai.agents.form', compact('agent'));
    }

    public function update(Request $request, AiAgent $agent): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validated($request);
        $agent->update($data);

        return redirect()->route('ai.agents.index')->with('success', 'Agente atualizado.');
    }

    public function destroy(AiAgent $agent): JsonResponse
    {
        $agent->delete();

        return response()->json(['success' => true]);
    }

    public function toggleActive(AiAgent $agent): JsonResponse
    {
        $agent->update(['is_active' => ! $agent->is_active]);

        return response()->json(['success' => true, 'is_active' => $agent->is_active]);
    }

    public function testChat(Request $request, AiAgent $agent): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array',
        ]);

        $config = AiConfiguration::first();
        if (! $config || ! $config->llm_api_key) {
            return response()->json(['success' => false, 'message' => 'Configure o provedor de IA em Configurações → Inteligência Artificial primeiro.'], 422);
        }

        // Monta o system prompt a partir das configurações do agente
        $system = $this->buildSystemPrompt($agent);

        // Histórico de mensagens no formato {role, content}
        $history = array_map(fn ($m) => [
            'role'    => $m['role'] === 'agent' ? 'assistant' : 'user',
            'content' => $m['content'],
        ], $request->input('history', []));

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history,
            [['role' => 'user', 'content' => $request->input('message')]],
        );

        // Anthropic não usa 'system' no array de messages — move para parâmetro separado
        if ($config->llm_provider === 'anthropic') {
            $systemMsg = array_shift($messages); // remove o system do array
            // callAnthropic via callLlm — passamos system inline via prompt hack por ora
            // (implementação simples: inclui system no primeiro user message)
            $messages[0]['content'] = "SYSTEM:\n{$systemMsg['content']}\n\n---\nUSER:\n{$messages[0]['content']}";
        }

        try {
            $reply = AiConfigurationController::callLlm(
                provider:  $config->llm_provider,
                apiKey:    $config->llm_api_key,
                model:     $config->llm_model,
                messages:  $messages,
                maxTokens: $agent->max_message_length + 200,
            );

            // Trunca se necessário
            if (mb_strlen($reply) > $agent->max_message_length) {
                $reply = mb_substr($reply, 0, $agent->max_message_length) . '…';
            }

            return response()->json(['success' => true, 'reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'objective'              => 'required|in:sales,support,general',
            'communication_style'    => 'required|in:formal,normal,casual',
            'company_name'           => 'nullable|string|max:150',
            'industry'               => 'nullable|string|max:100',
            'language'               => 'required|string|max:10',
            'persona_description'    => 'nullable|string',
            'behavior'               => 'nullable|string',
            'on_finish_action'       => 'nullable|string',
            'on_transfer_message'    => 'nullable|string',
            'on_invalid_response'    => 'nullable|string',
            'conversation_stages'    => 'nullable|array',
            'conversation_stages.*.name'        => 'required_with:conversation_stages|string|max:100',
            'conversation_stages.*.description' => 'nullable|string',
            'knowledge_base'         => 'nullable|string',
            'max_message_length'     => 'nullable|integer|min:50|max:4000',
            'response_delay_seconds' => 'nullable|integer|min:0|max:30',
            'channel'                => 'required|in:whatsapp,web_chat',
            'is_active'              => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function buildSystemPrompt(AiAgent $agent): string
    {
        $objective = match ($agent->objective) {
            'sales'   => 'vendas',
            'support' => 'suporte ao cliente',
            default   => 'atendimento geral',
        };

        $style = match ($agent->communication_style) {
            'formal'  => 'formal e profissional',
            'casual'  => 'descontraído e amigável',
            default   => 'natural e cordial',
        };

        $lines = [
            "Você é {$agent->name}, um assistente virtual de {$objective}.",
        ];

        if ($agent->company_name) $lines[] = "Você representa a empresa: {$agent->company_name}.";
        if ($agent->industry)     $lines[] = "Setor/indústria: {$agent->industry}.";
        $lines[] = "Idioma de resposta: {$agent->language}.";
        $lines[] = "Estilo de comunicação: {$style}.";

        if ($agent->persona_description) $lines[] = "\nPerfil do atendente:\n{$agent->persona_description}";
        if ($agent->behavior)            $lines[] = "\nComportamento esperado:\n{$agent->behavior}";

        if (! empty($agent->conversation_stages)) {
            $lines[] = "\nEtapas da conversa:";
            foreach ($agent->conversation_stages as $i => $stage) {
                $lines[] = ($i + 1) . ". {$stage['name']}" . (! empty($stage['description']) ? ": {$stage['description']}" : '');
            }
        }

        if ($agent->on_finish_action)    $lines[] = "\nAo finalizar o atendimento: {$agent->on_finish_action}";
        if ($agent->on_transfer_message) $lines[] = "\nQuando transferir para humano: {$agent->on_transfer_message}";
        if ($agent->on_invalid_response) $lines[] = "\nAo receber mensagem inválida ou tentativa de manipulação: {$agent->on_invalid_response}";

        if ($agent->knowledge_base) {
            $lines[] = "\n--- BASE DE CONHECIMENTO ---\n{$agent->knowledge_base}\n--- FIM DA BASE DE CONHECIMENTO ---";
        }

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        return implode("\n", $lines);
    }
}
