<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiAgentKnowledgeFile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $agents = AiAgent::withCount('conversations')->orderByDesc('created_at')->get();

        return view('tenant.ai.agents.index', compact('agents'));
    }

    public function create(): View
    {
        $agent          = new AiAgent();
        $knowledgeFiles = collect();
        $users          = $this->tenantUsers();

        return view('tenant.ai.agents.form', compact('agent', 'knowledgeFiles', 'users'));
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Limite de 1 agente por tenant
        if (AiAgent::exists()) {
            $message = 'Cada conta pode ter apenas 1 agente de IA. Edite o agente existente.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('ai.agents.index')->withErrors(['limit' => $message]);
        }

        $data  = $this->validated($request);
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
        $knowledgeFiles = $agent->knowledgeFiles()->orderBy('created_at')->get();
        $users          = $this->tenantUsers();

        return view('tenant.ai.agents.form', compact('agent', 'knowledgeFiles', 'users'));
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

        $provider = (string) config('ai.provider', 'openai');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', 'gpt-4o-mini');

        if ($apiKey === '') {
            return response()->json(['success' => false, 'message' => 'LLM_API_KEY não configurado no servidor.'], 422);
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

        try {
            $result = AiConfigurationController::callLlm(
                provider:  $provider,
                apiKey:    $apiKey,
                model:     $model,
                messages:  $messages,
                maxTokens: $agent->max_message_length + 200,
            );

            $reply = $result['reply'] ?? '';

            // Trunca se necessário
            if (mb_strlen($reply) > $agent->max_message_length) {
                $reply = mb_substr($reply, 0, $agent->max_message_length) . '…';
            }

            return response()->json(['success' => true, 'reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Knowledge Files ───────────────────────────────────────────────────────

    public function uploadKnowledgeFile(Request $request, AiAgent $agent): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,txt,csv,png,jpg,jpeg,webp,gif',
        ]);

        $uploaded  = $request->file('file');
        $mime      = $uploaded->getMimeType() ?? $uploaded->getClientMimeType();
        $origName  = $uploaded->getClientOriginalName();

        $path = $uploaded->store("ai-knowledge/{$agent->id}", 'public');

        $record = AiAgentKnowledgeFile::create([
            'ai_agent_id'   => $agent->id,
            'tenant_id'     => auth()->user()->tenant_id,
            'original_name' => $origName,
            'storage_path'  => $path,
            'mime_type'     => $mime,
            'status'        => 'pending',
        ]);

        try {
            $extractedText  = null;
            $errorMessage   = null;

            if (str_starts_with($mime, 'image/')) {
                // Descrever imagem via LLM Vision
                $fullPath = Storage::disk('public')->path($path);
                $base64   = base64_encode(file_get_contents($fullPath));
                $extractedText = $this->describeFileWithLlm($base64, $mime);
            } elseif ($mime === 'application/pdf') {
                // Extrair texto do PDF via smalot/pdfparser
                $fullPath = Storage::disk('public')->path($path);
                $parser   = new \Smalot\PdfParser\Parser();
                $pdf      = $parser->parseFile($fullPath);
                $text     = $pdf->getText();
                if (mb_strlen(trim($text)) < 10) {
                    throw new \RuntimeException('PDF sem texto legível. Converta as páginas em imagem e faça upload como PNG/JPG.');
                }
                // Trunca para não exceder tokens do LLM
                $extractedText = mb_substr($text, 0, 100000);
            } else {
                // TXT, CSV e outros textos — leitura direta
                $fullPath      = Storage::disk('public')->path($path);
                $extractedText = mb_substr(file_get_contents($fullPath), 0, 100000);
            }

            $record->update([
                'extracted_text' => $extractedText,
                'status'         => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::error('Knowledge file extraction failed', [
                'file_id' => $record->id,
                'error'   => $e->getMessage(),
            ]);
            $record->update([
                'status'        => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }

        $record->refresh();

        return response()->json([
            'id'            => $record->id,
            'original_name' => $record->original_name,
            'mime_type'     => $record->mime_type,
            'status'        => $record->status,
            'error_message' => $record->error_message,
            'preview'       => $record->extracted_text
                ? mb_substr($record->extracted_text, 0, 300) . (mb_strlen($record->extracted_text) > 300 ? '…' : '')
                : null,
        ]);
    }

    public function deleteKnowledgeFile(AiAgent $agent, AiAgentKnowledgeFile $file): JsonResponse
    {
        abort_unless($file->ai_agent_id === $agent->id, 404);

        Storage::disk('public')->delete($file->storage_path);
        $file->delete();

        return response()->json(['success' => true]);
    }

    private function describeFileWithLlm(string $base64, string $mimeType): string
    {
        $provider = (string) config('ai.provider', 'openai');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', 'gpt-4o-mini');

        if ($apiKey === '') {
            throw new \RuntimeException('LLM_API_KEY não configurado. Configure em Configurações → IA.');
        }

        $dataUri = "data:{$mimeType};base64,{$base64}";

        $messages = [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type'      => 'image_url',
                        'image_url' => ['url' => $dataUri],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Descreva em detalhes o conteúdo desta imagem/documento para servir como base de conhecimento de um assistente de vendas. Seja objetivo e capture todos os dados importantes (preços, produtos, nomes, especificações, informações de contato, etc.).',
                    ],
                ],
            ],
        ];

        $result = AiConfigurationController::callLlm(
            provider:  $provider,
            apiKey:    $apiKey,
            model:     $model,
            messages:  $messages,
            maxTokens: 2000,
        );

        return $result['reply'] ?? '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function tenantUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

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
            'response_wait_seconds'  => 'nullable|integer|min:0|max:30',
            'channel'                => 'required|in:whatsapp,web_chat,instagram',
            'is_active'              => 'nullable|boolean',
            'auto_assign'            => 'nullable|boolean',
            'enable_pipeline_tool'   => 'nullable|boolean',
            'enable_tags_tool'       => 'nullable|boolean',
            'enable_intent_notify'   => 'nullable|boolean',
            'transfer_to_user_id'    => 'nullable|integer|exists:users,id',
            'followup_enabled'           => 'nullable|boolean',
            'followup_delay_minutes'     => 'nullable|integer|min:5|max:1440',
            'followup_max_count'         => 'nullable|integer|min:1|max:10',
            'followup_hour_start'        => 'nullable|integer|min:0|max:23',
            'followup_hour_end'          => 'nullable|integer|min:1|max:23',
            'enable_calendar_tool'       => 'nullable|boolean',
            'calendar_tool_instructions' => 'nullable|string|max:2000',
        ]);

        $data['is_active']              = $request->boolean('is_active');
        $data['auto_assign']            = $request->boolean('auto_assign');
        $data['enable_pipeline_tool']   = $request->boolean('enable_pipeline_tool');
        $data['enable_tags_tool']       = $request->boolean('enable_tags_tool');
        $data['enable_intent_notify']   = $request->boolean('enable_intent_notify');
        $data['followup_enabled']       = $request->boolean('followup_enabled');
        $data['enable_calendar_tool']   = $request->boolean('enable_calendar_tool');
        $data['transfer_to_user_id']    = $request->input('transfer_to_user_id') ?: null;

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

        // Arquivos de conhecimento carregados
        $kbFiles = $agent->knowledgeFiles()->where('status', 'done')->get();
        foreach ($kbFiles as $kbFile) {
            if ($kbFile->extracted_text) {
                $lines[] = "\n--- ARQUIVO: {$kbFile->original_name} ---\n{$kbFile->extracted_text}\n--- FIM DO ARQUIVO ---";
            }
        }

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        return implode("\n", $lines);
    }
}
