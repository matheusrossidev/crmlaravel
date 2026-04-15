<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiAgentKnowledgeFile;
use App\Services\PlanLimitChecker;
use App\Models\AiAgentMedia;
use App\Models\AiUsageLog;
use App\Models\PlanDefinition;
use App\Models\TenantTokenIncrement;
use App\Models\TokenIncrementPlan;
use App\Models\Department;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\AgnoService;
use App\Services\ElevenLabsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $agents = AiAgent::withCount('conversations')->orderByDesc('created_at')->get();
        $tenant = activeTenant();
        $plan   = PlanDefinition::where('name', $tenant->plan)->first();

        $tokensUsedMonth = (int) AiUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_total');

        $tokensLimit = (int) ($plan?->features_json['ai_tokens_monthly'] ?? 0);

        $tokenIncrementPlans = TokenIncrementPlan::where('is_active', true)
            ->orderBy('tokens_amount')
            ->get();

        $dailyUsage = AiUsageLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(tokens_total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('tenant.ai.agents.index', compact(
            'agents', 'tokensUsedMonth', 'tokensLimit', 'tokenIncrementPlans', 'dailyUsage'
        ));
    }

    public function create(): View
    {
        $agent          = new AiAgent();
        $knowledgeFiles = collect();
        $users          = $this->tenantUsers();
        $departments    = $this->activeDepartments();
        $whatsappInstances = WhatsappInstance::orderBy('label')->get(['id', 'label', 'phone_number', 'session_name']);

        // Wizard multi-step pra criação (form sectioned é só pra edit)
        return view('tenant.ai.agents.wizard', compact('agent', 'knowledgeFiles', 'users', 'departments', 'whatsappInstances'));
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $limitMsg = PlanLimitChecker::check('ai_agents');
        if ($limitMsg) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
            }
            return redirect()->route('ai.agents.index')->withErrors(['limit' => $limitMsg]);
        }

        $data = $this->validated($request);

        // Generate website_token for web_chat agents
        if (($data['channel'] ?? '') === 'web_chat' && empty($data['website_token'])) {
            $data['website_token'] = (string) Str::uuid();
        }

        $agent = AiAgent::create($data);

        if ($request->has('whatsapp_instance_ids')) {
            $agent->whatsappInstances()->sync($request->input('whatsapp_instance_ids', []));
        }

        $this->syncToAgno($agent);

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
        $agent->load('mediaFiles', 'whatsappInstances');
        $users       = $this->tenantUsers();
        $departments = $this->activeDepartments();
        $whatsappInstances = WhatsappInstance::orderBy('label')->get(['id', 'label', 'phone_number', 'session_name']);

        $embedScriptUrl = null;
        if ($agent->website_token) {
            $embedScriptUrl = rtrim((string) config('app.url'), '/') . '/api/widget/' . $agent->website_token . '.js';
        }

        return view('tenant.ai.agents.form', compact('agent', 'knowledgeFiles', 'users', 'departments', 'whatsappInstances', 'embedScriptUrl'));
    }

    public function update(Request $request, AiAgent $agent): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        // Generate website_token for web_chat agents if not set
        if (($data['channel'] ?? '') === 'web_chat' && ! $agent->website_token) {
            $data['website_token'] = (string) Str::uuid();
        }

        $agent->update($data);

        if ($request->has('whatsapp_instance_ids')) {
            $agent->whatsappInstances()->sync($request->input('whatsapp_instance_ids', []));
        }

        $agent->refresh();

        $this->syncToAgno($agent);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('ai.agents.edit', $agent->id)]);
        }

        return redirect()->route('ai.agents.index')->with('success', 'Agente atualizado.');
    }

    public function onboarding(): View
    {
        return view('tenant.ai.agents.onboarding');
    }

    public function destroy(AiAgent $agent): JsonResponse
    {
        // Cleanup media files from storage before cascade delete
        foreach ($agent->mediaFiles as $media) {
            Storage::disk('public')->delete($media->storage_path);
        }
        foreach ($agent->knowledgeFiles as $kf) {
            Storage::disk('public')->delete($kf->storage_path);
        }

        $agent->delete();

        return response()->json(['success' => true]);
    }

    public function uploadMedia(Request $request, AiAgent $agent): JsonResponse
    {
        $request->validate([
            'file'        => 'required|file|max:20480|mimes:png,jpg,jpeg,webp,gif,pdf,doc,docx',
            'description' => 'required|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store("ai-agent-media/{$agent->id}", 'public');

        $record = AiAgentMedia::create([
            'ai_agent_id'   => $agent->id,
            'tenant_id'     => $agent->tenant_id,
            'original_name' => preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file->getClientOriginalName())),
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'description'   => $request->input('description'),
        ]);

        return response()->json([
            'id'            => $record->id,
            'original_name' => $record->original_name,
            'mime_type'     => $record->mime_type,
            'file_size'     => $record->file_size,
            'description'   => $record->description,
            'url'           => asset('storage/' . $record->storage_path),
        ]);
    }

    public function deleteMedia(AiAgent $agent, AiAgentMedia $media): JsonResponse
    {
        abort_unless($media->ai_agent_id === $agent->id, 404);

        Storage::disk('public')->delete($media->storage_path);
        $media->delete();

        return response()->json(['success' => true]);
    }

    public function voices(): JsonResponse
    {
        $service = app(ElevenLabsService::class);

        if (! $service->isAvailable()) {
            return response()->json(['success' => false, 'voices' => [], 'message' => 'ElevenLabs API Key não configurada.']);
        }

        $voices = $service->getVoices();

        return response()->json(['success' => true, 'voices' => $voices]);
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
            'file' => 'required|file|max:20480|mimes:pdf,doc,docx,txt,csv,png,jpg,jpeg,webp,gif',
        ]);

        $uploaded  = $request->file('file');
        $mime      = $uploaded->getMimeType() ?? $uploaded->getClientMimeType();
        $origName  = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($uploaded->getClientOriginalName()));

        $path = $uploaded->store("ai-knowledge/{$agent->id}", 'public');

        $record = AiAgentKnowledgeFile::create([
            'ai_agent_id'   => $agent->id,
            'tenant_id'     => activeTenantId(),
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
            } elseif (in_array($mime, [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                'application/msword', // .doc
                'application/zip', // alguns .docx chegam como zip generico
            ], true)) {
                $fullPath = Storage::disk('public')->path($path);
                $text     = $this->extractDocxText($fullPath);
                if (mb_strlen(trim($text)) < 10) {
                    throw new \RuntimeException('Documento Word sem texto legivel ou formato nao suportado.');
                }
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

            // Indexar no Agno para RAG (se o agente usa Agno)
            if ($agent->use_agno && $extractedText) {
                $indexResult = app(AgnoService::class)->indexFile(
                    $agent->id,
                    $agent->tenant_id,
                    $record->id,
                    $extractedText,
                    $origName,
                );

                if ($indexResult && ($indexResult['ok'] ?? false)) {
                    $record->update([
                        'chunks_count'   => $indexResult['chunks_count'] ?? 0,
                        'indexed_at'     => now(),
                        'indexing_error' => null,
                    ]);

                    // Loga consumo de tokens de embedding (text-embedding-3-small ~ $0.02/1M)
                    try {
                        \App\Models\AiUsageLog::create([
                            'tenant_id'         => $agent->tenant_id,
                            'conversation_id'   => null,
                            'model'             => 'text-embedding-3-small',
                            'provider'          => 'openai',
                            'tokens_prompt'     => $indexResult['tokens_used'] ?? 0,
                            'tokens_completion' => 0,
                            'tokens_total'      => $indexResult['tokens_used'] ?? 0,
                            'type'              => 'knowledge_indexing',
                        ]);
                    } catch (\Throwable) {}
                } else {
                    $record->update([
                        'indexing_error' => $indexResult['error'] ?? 'Falha ao indexar no Agno',
                    ]);
                }
            }
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

        // Apaga chunks vetorizados no Agno antes de remover o arquivo
        if ($agent->use_agno) {
            app(AgnoService::class)->deleteKnowledgeFile($agent->id, $file->id);
        }

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

    /**
     * Extrai texto plano de um arquivo .docx ou .doc usando phpoffice/phpword.
     * Itera todos os elementos do documento (paragrafos, tabelas, headers/footers,
     * listas) e concatena o texto. Quebras de linha entre paragrafos preservadas.
     */
    private function extractDocxText(string $path): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);

        $lines = [];
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->collectDocxText($element, $lines);
            }
        }

        return trim(implode("\n", array_filter($lines, fn ($l) => trim($l) !== '')));
    }

    /**
     * Recursivamente coleta texto de elementos do PhpWord. Suporta TextRun,
     * Text, Table, ListItem, Title — e ignora silenciosamente o que nao tem texto
     * (imagens, line breaks soltos, etc).
     */
    private function collectDocxText($element, array &$lines): void
    {
        // Text e TextBreak
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text) && trim($text) !== '') {
                $lines[] = $text;
                return;
            }
        }

        // TextRun (paragrafo) — itera filhos
        if (method_exists($element, 'getElements')) {
            $buffer = [];
            foreach ($element->getElements() as $child) {
                $childLines = [];
                $this->collectDocxText($child, $childLines);
                $buffer = array_merge($buffer, $childLines);
            }
            if (! empty($buffer)) {
                // Texto inline do mesmo paragrafo: junta com espaco
                $lines[] = implode(' ', $buffer);
            }
            return;
        }

        // Table — itera linhas e celulas
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                $rowParts = [];
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $cellLines = [];
                        $this->collectDocxText($cellElement, $cellLines);
                        if (! empty($cellLines)) {
                            $rowParts[] = implode(' ', $cellLines);
                        }
                    }
                }
                if (! empty($rowParts)) {
                    $lines[] = implode(' | ', $rowParts);
                }
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function tenantUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function activeDepartments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('is_active', true)
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
            'transfer_to_user_id'         => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('users', 'id')->where('tenant_id', activeTenantId())],
            'transfer_to_department_id'   => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('departments', 'id')->where('tenant_id', activeTenantId())],
            'followup_enabled'           => 'nullable|boolean',
            'followup_delay_minutes'     => 'nullable|integer|min:5|max:1440',
            'followup_max_count'         => 'nullable|integer|min:1|max:10',
            'followup_hour_start'        => 'nullable|integer|min:0|max:23',
            'followup_hour_end'          => 'nullable|integer|min:1|max:23',
            'followup_strategy'          => 'nullable|in:smart,template,off',
            'followup_template_id'       => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('whatsapp_templates', 'id')->where('tenant_id', activeTenantId())->where('status', 'APPROVED')],
            'enable_calendar_tool'       => 'nullable|boolean',
            'calendar_tool_instructions'  => 'nullable|string|max:2000',
            'calendar_id'                => 'nullable|string|max:191',
            'reminder_offsets'           => 'nullable|array',
            'reminder_offsets.*'         => 'integer|min:1',
            'reminder_message_template'  => 'nullable|string|max:1000',
            // Avatar decorativo admin-only (lista + sidebar do edit) — NÃO vai pro lead
            'display_avatar'             => 'nullable|string|max:191',
            // Widget fields (web_chat channel)
            'bot_name'                   => 'nullable|string|max:100',
            'bot_avatar'                 => 'nullable|string|max:500',
            'welcome_message'            => 'nullable|string|max:1000',
            'widget_type'                => 'nullable|in:bubble,inline',
            'widget_color'               => 'nullable|string|max:10',
            'enable_voice_reply'         => 'nullable|boolean',
            'elevenlabs_voice_id'        => 'nullable|string|max:100',
        ]);

        $data['is_active']              = $request->boolean('is_active');
        $data['auto_assign']            = $request->boolean('auto_assign');
        $data['enable_pipeline_tool']   = $request->boolean('enable_pipeline_tool');
        $data['enable_tags_tool']       = $request->boolean('enable_tags_tool');
        $data['enable_intent_notify']   = $request->boolean('enable_intent_notify');
        $data['followup_enabled']       = $request->boolean('followup_enabled');
        $data['enable_calendar_tool']    = $request->boolean('enable_calendar_tool');
        $data['calendar_id']            = $request->input('calendar_id') ?: null;
        $data['reminder_offsets']        = $request->input('reminder_offsets') ? array_map('intval', $request->input('reminder_offsets')) : [1440, 60];
        $data['reminder_message_template'] = $request->input('reminder_message_template') ?: null;
        $data['enable_voice_reply']     = $request->boolean('enable_voice_reply');
        $data['use_agno']               = true; // Todos os agentes usam Agno
        $data['transfer_to_user_id']         = $request->input('transfer_to_user_id') ?: null;
        $data['transfer_to_department_id']   = $request->input('transfer_to_department_id') ?: null;

        return $data;
    }

    private function syncToAgno(AiAgent $agent): void
    {
        if (! $agent->use_agno) {
            return;
        }

        app(AgnoService::class)->configureAgent($agent->id, [
            'tenant_id'            => $agent->tenant_id,
            'name'                 => $agent->name,
            'objective'            => $agent->objective,
            'company_name'         => $agent->company_name ?? '',
            'industry'             => $agent->industry ?? '',
            'communication_style'  => $agent->communication_style,
            'persona_description'  => $agent->persona_description ?? '',
            'behavior'             => $agent->behavior ?? '',
            'max_message_length'   => $agent->max_message_length ?? 800,
            'knowledge_base_text'  => $agent->knowledge_base ?? '',
            'llm_provider'         => config('ai.provider', 'openai'),
            'llm_model'            => config('ai.model', 'gpt-4o-mini'),
            'llm_api_key'          => config('ai.api_key', ''),
            'enable_pipeline_tool' => (bool) $agent->enable_pipeline_tool,
            'enable_tags_tool'     => (bool) $agent->enable_tags_tool,
            'enable_intent_notify' => (bool) $agent->enable_intent_notify,
            'enable_calendar_tool' => (bool) $agent->enable_calendar_tool,
            'language'             => $agent->language ?? 'pt-BR',
        ]);
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

        // Mídias disponíveis para envio
        $mediaFiles = $agent->mediaFiles()->get();
        if ($mediaFiles->isNotEmpty()) {
            $lines[] = "\n--- MÍDIAS DISPONÍVEIS PARA ENVIO ---";
            $lines[] = "Você pode enviar arquivos ao contato. Use SOMENTE quando relevante.";
            foreach ($mediaFiles as $media) {
                $lines[] = "  media_id {$media->id}: {$media->original_name} — {$media->description}";
            }
            $lines[] = "--- FIM DAS MÍDIAS ---";
        }

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        return implode("\n", $lines);
    }
}
