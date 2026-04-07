<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\LostSaleReason;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\ScoringRule;
use App\Models\StageRequiredTask;
use App\Models\Tenant;
use App\Models\WhatsappQuickMessage;
use App\Models\WhatsappTag;
use App\Support\PipelineTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateCRMFromAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1;

    /**
     * Mapeamento niche (wizard) → categoria (PipelineTemplates).
     * Quando o usuário escolhe um nicho mas NÃO seleciona um template
     * específico, e a IA falha, usamos o primeiro template dessa categoria
     * como fallback inteligente.
     */
    private const NICHE_TO_CATEGORY = [
        'imobiliario' => 'imobiliaria',
        'estetica'    => 'beleza_estetica',
        'educacao'    => 'educacao',
        'saude'       => 'saude',
        'varejo'      => 'vendas_b2c',
        'b2b'         => 'servicos_b2b',
        'tecnologia'  => 'tecnologia_saas',
        'outro'       => null,
    ];

    public function __construct(
        private Tenant $tenant,
        private array $answers,
        private ?string $pipelineTemplateSlug = null,
    ) {}

    public function handle(): void
    {
        $cacheKey = "onboarding:progress:{$this->tenant->id}";
        $aiUsedFallback = false;

        try {
            // Step 1: Analyzing
            $this->markStep($cacheKey, 'analyzing');

            $json = $this->callOpenAI();

            if (! $json) {
                // Fallback inteligente: usa template do PipelineTemplates baseado no nicho
                // + sequences/scoring/automations padrões pra não deixar arrays vazios
                $json = $this->getFallbackData();
                $aiUsedFallback = true;
            }

            // Step 2: Pipeline — se template slug foi informado pelo wizard, usa o template
            //                   direto e ignora $json['pipeline'] (mesmo se a IA tiver gerado um)
            $this->markStep($cacheKey, 'pipeline');
            if ($this->pipelineTemplateSlug) {
                $pipelineResult = $this->createPipelineFromTemplate($this->pipelineTemplateSlug);
            } else {
                $pipelineResult = $this->createPipeline($json['pipeline'] ?? []);
            }

            // Step 3: Sequences
            $this->markStep($cacheKey, 'sequences');
            $sequenceResult = $this->createSequences($json['sequences'] ?? []);

            // Step 4: Automations
            $this->markStep($cacheKey, 'automations');
            $automationResult = $this->createAutomations($json['automations'] ?? []);

            // Step 5: Scoring
            $this->markStep($cacheKey, 'scoring');
            $scoringResult = $this->createScoringRules($json['scoring_rules'] ?? []);

            // Step 6: AI Agent
            $this->markStep($cacheKey, 'ai_agent');
            $agentResult = $this->createAiAgent($json['ai_agent'] ?? []);

            // Step 7: Quick Messages
            $this->markStep($cacheKey, 'quick_messages');
            $quickMsgResult = $this->createQuickMessages($json['quick_messages'] ?? []);

            // Step 8: Tags + Loss Reasons
            $this->markStep($cacheKey, 'config');
            $this->createTags($json['tags'] ?? []);
            $this->createLossReasons($json['loss_reasons'] ?? []);

            // Done
            $this->tenant->update(['onboarding_completed_at' => now()]);

            $resultData = [
                'pipeline'       => $pipelineResult,
                'sequences'      => $sequenceResult,
                'automations'    => $automationResult,
                'scoring'        => $scoringResult,
                'ai_agent'       => $agentResult,
                'quick_messages' => $quickMsgResult,
                'tags'           => $json['tags'] ?? [],
                'loss_reasons'   => $json['loss_reasons'] ?? [],
                'used_fallback'  => $aiUsedFallback,
                'used_template'  => $this->pipelineTemplateSlug,
            ];

            Cache::put("onboarding:result:{$this->tenant->id}", $resultData, 3600);
            Cache::put($cacheKey, [
                'status'    => 'done',
                'completed' => ['analyzing', 'pipeline', 'sequences', 'automations', 'scoring', 'ai_agent', 'quick_messages', 'config'],
                'total'     => 8,
                'error'     => null,
                'fallback'  => $aiUsedFallback,
            ], 600);

        } catch (\Throwable $e) {
            Log::error('GenerateCRMFromAI failed', [
                'tenant_id' => $this->tenant->id,
                'error'     => $e->getMessage(),
                'trace'     => substr($e->getTraceAsString(), 0, 1000),
            ]);

            Cache::put($cacheKey, [
                'status'    => 'error',
                'completed' => Cache::get($cacheKey)['completed'] ?? [],
                'total'     => 8,
                'error'     => $e->getMessage(),
            ], 600);

            // NÃO marcar onboarding_completed_at — user verá banner de erro
            // e poderá clicar em "Tentar novamente" pra re-rodar o job.
        }
    }

    private function markStep(string $cacheKey, string $stepName): void
    {
        $progress = Cache::get($cacheKey, ['status' => 'processing', 'completed' => [], 'total' => 8, 'error' => null]);
        $progress['completed'][] = $stepName;
        $progress['status'] = 'processing';
        Cache::put($cacheKey, $progress, 600);
    }

    // ── OpenAI Call ──────────────────────────────────────────────────

    private function callOpenAI(): ?array
    {
        $apiKey = (string) config('ai.api_key', '');
        $model  = (string) config('ai.model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            Log::warning('GenerateCRMFromAI: LLM_API_KEY not configured');
            return null;
        }

        $prompt = $this->buildPrompt();

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are a CRM configuration expert. Return ONLY valid JSON, no markdown, no explanation.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens'  => 4000,
                ]);

            if (! $response->successful()) {
                Log::error('GenerateCRMFromAI: OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $content = $response->json('choices.0.message.content', '');
            $content = trim($content);

            // Strip markdown code fences if present
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
                $content = preg_replace('/```\s*$/m', '', $content);
                $content = trim($content);
            }

            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('GenerateCRMFromAI: Invalid JSON from AI', ['content' => substr($content, 0, 500)]);
                return null;
            }

            return $parsed;

        } catch (\Throwable $e) {
            Log::error('GenerateCRMFromAI: API call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(): string
    {
        $a    = $this->answers;
        $lang = ($a['locale'] ?? 'pt_BR') === 'en' ? 'English' : 'Brazilian Portuguese';

        // Sales process: só inclui no prompt se o usuário preencheu (campo opcional agora)
        $salesProcessLine = ! empty($a['sales_process'])
            ? "- Sales process described by user: \"{$a['sales_process']}\""
            : '';

        // Pipeline section: omitida quando o usuário escolheu um template pré-pronto
        // (nesse caso a IA não precisa gerar pipeline, só sequences/automations/etc)
        $usesTemplate = $this->pipelineTemplateSlug !== null;

        $pipelineSchema = $usesTemplate ? '' : <<<'JSON'

  "pipeline": {
    "name": "Pipeline name",
    "stages": [
      {"name": "Stage 1", "color": "#6B7280"},
      {"name": "Stage 2", "color": "#3B82F6"},
      {"name": "Stage 3", "color": "#F59E0B"},
      {"name": "Stage 4", "color": "#8B5CF6"},
      {"name": "Won Stage", "color": "#10B981", "is_won": true},
      {"name": "Lost Stage", "color": "#EF4444", "is_lost": true}
    ]
  },
JSON;

        $pipelineRule = $usesTemplate
            ? '- DO NOT generate a pipeline — the user already chose a pre-built pipeline template'
            : '- Pipeline must have 4-6 stages + 1 won + 1 lost stage';

        return <<<PROMPT
Generate a complete CRM setup for this business. All text content MUST be in {$lang}.

BUSINESS CONTEXT:
- Industry: {$a['niche']}
- Channels: {$this->formatArray($a['channels'])}
{$salesProcessLine}
- Biggest challenge: {$a['difficulty']}
- Team size: {$a['team_size']}

Return a JSON object with this EXACT structure:

{{$pipelineSchema}
  "sequences": [
    {
      "name": "Sequence name",
      "description": "Brief description",
      "steps": [
        {"type": "message", "delay_minutes": 0, "config": {"body": "Message text with {{nome}} variable"}},
        {"type": "message", "delay_minutes": 1440, "config": {"body": "Follow-up message"}},
        {"type": "message", "delay_minutes": 2880, "config": {"body": "Final message"}}
      ]
    }
  ],
  "automations": [
    {
      "name": "Automation name",
      "trigger_type": "message_received|lead_created|lead_stage_changed",
      "actions": [{"type": "add_tag_lead", "config": {"tag": "tag name"}}]
    }
  ],
  "scoring_rules": [
    {"name": "Rule name", "category": "engagement|pipeline|profile", "event_type": "message_received|stage_advanced|fast_reply|profile_complete|inactive_3d", "points": 10, "cooldown_hours": 1}
  ],
  "ai_agent": {
    "name": "Agent name",
    "objective": "Agent objective",
    "persona_description": "How the agent should behave",
    "communication_style": "professional|friendly|casual",
    "knowledge_base": "Key information about the business"
  },
  "quick_messages": [
    {"title": "Template title", "body": "Template body with {{nome}} variable"}
  ],
  "tags": ["Tag1", "Tag2", "Tag3", "Tag4", "Tag5", "Tag6", "Tag7"],
  "loss_reasons": ["Reason 1", "Reason 2", "Reason 3", "Reason 4", "Reason 5"]
}

RULES:
{$pipelineRule}
- Create 2-3 sequences with 3-4 steps each, tailored to the challenge
- Create 3-5 automations based on the challenge (e.g. if "forget follow-up" → create follow-up reminders)
- Create 4-6 scoring rules relevant to the industry
- AI agent persona should match the industry tone
- Create 5-8 quick messages common for the industry
- Create 7-10 tags relevant to the business
- Create 5-7 loss reasons specific to the industry
- Use {{nome}} and {{empresa}} as variables in messages
- All text in {$lang}
PROMPT;
    }

    private function formatArray(array $items): string
    {
        return implode(', ', $items);
    }

    // ── Fallback (if AI fails) ──────────────────────────────────────

    /**
     * Fallback inteligente quando OpenAI falha:
     *
     * - Pipeline: tenta achar template do PipelineTemplates baseado no nicho
     *   do usuário. Se não achar, usa o pipeline genérico de 6 stages.
     * - Sequences/scoring/automations: defaults sensatos por nicho (não mais
     *   arrays vazios como era antes).
     * - Quando o wizard já mandou um $pipelineTemplateSlug, este método NÃO
     *   gera pipeline (o handle() vai usar createPipelineFromTemplate diretamente).
     */
    private function getFallbackData(): array
    {
        $isEn  = ($this->answers['locale'] ?? 'pt_BR') === 'en';
        $niche = $this->answers['niche'] ?? 'outro';

        // Pipeline: só preenche se não houver template_slug — neste caso o handle
        // ignora json['pipeline'] e usa createPipelineFromTemplate.
        // Se não houver template_slug, tentamos buscar um template por nicho.
        $pipeline = [];
        if (! $this->pipelineTemplateSlug) {
            $template = $this->findTemplateForNiche($niche);
            if ($template) {
                $pipeline = [
                    'name'   => $template['name'],
                    'stages' => array_map(fn ($s) => [
                        'name'           => $s['name'],
                        'color'          => $s['color'] ?? '#6B7280',
                        'is_won'         => $s['is_won'] ?? false,
                        'is_lost'        => $s['is_lost'] ?? false,
                        'required_tasks' => $s['required_tasks'] ?? [],
                    ], $template['stages']),
                ];
            } else {
                // Pipeline genérico (apenas pra nicho 'outro' ou nichos sem template)
                $pipeline = [
                    'name'   => $isEn ? 'Sales Pipeline' : 'Funil de Vendas',
                    'stages' => [
                        ['name' => $isEn ? 'New Lead' : 'Novo Lead', 'color' => '#6B7280'],
                        ['name' => $isEn ? 'Contacted' : 'Em Contato', 'color' => '#3B82F6'],
                        ['name' => $isEn ? 'Proposal' : 'Proposta', 'color' => '#F59E0B'],
                        ['name' => $isEn ? 'Negotiation' : 'Negociação', 'color' => '#8B5CF6'],
                        ['name' => $isEn ? 'Won' : 'Ganho', 'color' => '#10B981', 'is_won' => true],
                        ['name' => $isEn ? 'Lost' : 'Perdido', 'color' => '#EF4444', 'is_lost' => true],
                    ],
                ];
            }
        }

        return [
            'pipeline'       => $pipeline,
            'sequences'      => $this->fallbackSequences($isEn),
            'automations'    => $this->fallbackAutomations($isEn),
            'scoring_rules'  => $this->fallbackScoringRules($isEn),
            'ai_agent'       => $this->fallbackAiAgent($isEn),
            'quick_messages' => $this->fallbackQuickMessages($isEn),
            'tags'           => $isEn
                ? ['Hot', 'Warm', 'Cold', 'Priority', 'Follow-up', 'Returning', 'New']
                : ['Quente', 'Morno', 'Frio', 'Prioritário', 'Retorno', 'Recorrente', 'Novo'],
            'loss_reasons'   => $isEn
                ? ['Too expensive', 'No interest', 'Chose competitor', 'No response', 'Bad timing']
                : ['Preço alto', 'Sem interesse', 'Optou por concorrente', 'Sem retorno', 'Timing errado'],
        ];
    }

    /**
     * Acha o primeiro template do PipelineTemplates pra um nicho do wizard.
     * Retorna null se nicho não tem mapping ou categoria não tem templates.
     */
    private function findTemplateForNiche(string $niche): ?array
    {
        $category = self::NICHE_TO_CATEGORY[$niche] ?? null;
        if (! $category) {
            return null;
        }

        foreach (PipelineTemplates::all() as $template) {
            if (($template['category'] ?? null) === $category) {
                return $template;
            }
        }

        return null;
    }

    private function fallbackSequences(bool $isEn): array
    {
        if ($isEn) {
            return [
                [
                    'name' => 'Welcome new lead',
                    'description' => '3-message warm welcome for fresh leads',
                    'steps' => [
                        ['type' => 'message', 'delay_minutes' => 0,    'config' => ['body' => 'Hi {{nome}}! Thanks for reaching out. How can I help you today?']],
                        ['type' => 'message', 'delay_minutes' => 1440, 'config' => ['body' => 'Hi {{nome}}, just checking in. Did my last message reach you?']],
                        ['type' => 'message', 'delay_minutes' => 4320, 'config' => ['body' => 'Hi {{nome}}, last try here. Let me know if you\'d like to chat — happy to help!']],
                    ],
                ],
                [
                    'name' => 'Re-engagement',
                    'description' => 'Bring back leads that went cold',
                    'steps' => [
                        ['type' => 'message', 'delay_minutes' => 0,    'config' => ['body' => 'Hi {{nome}}, it\'s been a while! Anything I can help with?']],
                        ['type' => 'message', 'delay_minutes' => 2880, 'config' => ['body' => 'Just bumping this — let me know if the timing is better now.']],
                    ],
                ],
            ];
        }

        return [
            [
                'name' => 'Boas-vindas',
                'description' => 'Sequência de boas-vindas para leads novos',
                'steps' => [
                    ['type' => 'message', 'delay_minutes' => 0,    'config' => ['body' => 'Olá {{nome}}! Obrigado pelo contato. Como posso te ajudar hoje?']],
                    ['type' => 'message', 'delay_minutes' => 1440, 'config' => ['body' => 'Oi {{nome}}, só passando pra confirmar se minha mensagem chegou. Posso te ajudar com algo?']],
                    ['type' => 'message', 'delay_minutes' => 4320, 'config' => ['body' => 'Oi {{nome}}, última tentativa por aqui. Me avisa se quiser conversar — fico à disposição!']],
                ],
            ],
            [
                'name' => 'Reengajamento',
                'description' => 'Reativar leads que esfriaram',
                'steps' => [
                    ['type' => 'message', 'delay_minutes' => 0,    'config' => ['body' => 'Oi {{nome}}, faz um tempinho! Posso te ajudar com algo?']],
                    ['type' => 'message', 'delay_minutes' => 2880, 'config' => ['body' => 'Só passando aqui — se o momento agora for melhor, é só me avisar.']],
                ],
            ],
        ];
    }

    private function fallbackAutomations(bool $isEn): array
    {
        if ($isEn) {
            return [
                [
                    'name'         => 'Tag new WhatsApp leads',
                    'trigger_type' => 'message_received',
                    'actions'      => [['type' => 'add_tag_lead', 'config' => ['tag' => 'New']]],
                ],
                [
                    'name'         => 'Tag won deals',
                    'trigger_type' => 'lead_stage_changed',
                    'actions'      => [['type' => 'add_tag_lead', 'config' => ['tag' => 'Customer']]],
                ],
            ];
        }

        return [
            [
                'name'         => 'Marcar leads novos do WhatsApp',
                'trigger_type' => 'message_received',
                'actions'      => [['type' => 'add_tag_lead', 'config' => ['tag' => 'Novo']]],
            ],
            [
                'name'         => 'Marcar vendas fechadas',
                'trigger_type' => 'lead_stage_changed',
                'actions'      => [['type' => 'add_tag_lead', 'config' => ['tag' => 'Cliente']]],
            ],
        ];
    }

    private function fallbackScoringRules(bool $isEn): array
    {
        if ($isEn) {
            return [
                ['name' => 'Message received',  'category' => 'engagement', 'event_type' => 'message_received', 'points' => 5,  'cooldown_hours' => 1],
                ['name' => 'Fast reply',         'category' => 'engagement', 'event_type' => 'fast_reply',       'points' => 10, 'cooldown_hours' => 1],
                ['name' => 'Stage advanced',     'category' => 'pipeline',   'event_type' => 'stage_advanced',   'points' => 15, 'cooldown_hours' => 0],
                ['name' => 'Profile completed',  'category' => 'profile',    'event_type' => 'profile_complete', 'points' => 20, 'cooldown_hours' => 0],
            ];
        }

        return [
            ['name' => 'Mensagem recebida',  'category' => 'engagement', 'event_type' => 'message_received', 'points' => 5,  'cooldown_hours' => 1],
            ['name' => 'Resposta rápida',    'category' => 'engagement', 'event_type' => 'fast_reply',       'points' => 10, 'cooldown_hours' => 1],
            ['name' => 'Avançou de etapa',   'category' => 'pipeline',   'event_type' => 'stage_advanced',   'points' => 15, 'cooldown_hours' => 0],
            ['name' => 'Perfil completo',    'category' => 'profile',    'event_type' => 'profile_complete', 'points' => 20, 'cooldown_hours' => 0],
        ];
    }

    private function fallbackAiAgent(bool $isEn): array
    {
        if ($isEn) {
            return [
                'name'                => 'Sales Assistant',
                'objective'           => 'Qualify leads, answer common questions, schedule meetings',
                'persona_description' => 'Friendly, professional, helpful. Always greets the lead by name.',
                'communication_style' => 'friendly',
                'knowledge_base'      => 'You are a sales assistant. Be helpful, ask qualifying questions, and route complex requests to a human.',
            ];
        }

        return [
            'name'                => 'Assistente de Vendas',
            'objective'           => 'Qualificar leads, responder perguntas comuns, agendar reuniões',
            'persona_description' => 'Amigável, profissional e prestativo. Sempre cumprimenta o lead pelo nome.',
            'communication_style' => 'friendly',
            'knowledge_base'      => 'Você é um assistente de vendas. Seja prestativo, faça perguntas de qualificação e transfira casos complexos para um humano.',
        ];
    }

    private function fallbackQuickMessages(bool $isEn): array
    {
        if ($isEn) {
            return [
                ['title' => 'Greeting',          'body' => 'Hi {{nome}}! How can I help you today?'],
                ['title' => 'Send proposal',     'body' => 'Hi {{nome}}, here\'s the proposal we discussed.'],
                ['title' => 'Schedule meeting',  'body' => 'Hi {{nome}}, when would be a good time for a quick call?'],
                ['title' => 'Thanks',            'body' => 'Thanks {{nome}}! Talk soon.'],
                ['title' => 'Follow up',         'body' => 'Hi {{nome}}, just checking in — anything I can help with?'],
            ];
        }

        return [
            ['title' => 'Saudação',          'body' => 'Olá {{nome}}! Como posso te ajudar hoje?'],
            ['title' => 'Enviar proposta',   'body' => 'Olá {{nome}}, segue a proposta que conversamos.'],
            ['title' => 'Agendar reunião',   'body' => 'Olá {{nome}}, qual o melhor horário para a gente conversar?'],
            ['title' => 'Agradecimento',     'body' => 'Obrigado {{nome}}! Falo com você em breve.'],
            ['title' => 'Follow up',         'body' => 'Oi {{nome}}, só passando aqui pra ver se posso te ajudar com algo.'],
        ];
    }

    // ── Create records ──────────────────────────────────────────────

    private function createPipeline(array $data): array
    {
        if (empty($data['stages'])) {
            return ['name' => $data['name'] ?? 'Pipeline', 'stages_count' => 0];
        }

        $pipeline = Pipeline::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => $data['name'] ?? 'Pipeline',
            'color'      => '#0085f3',
            'is_default' => true,
            'sort_order' => 1,
        ]);

        foreach ($data['stages'] as $i => $stage) {
            $createdStage = PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name'        => $stage['name'],
                'color'       => $stage['color'] ?? '#6B7280',
                'position'    => $i + 1,
                'is_won'      => $stage['is_won'] ?? false,
                'is_lost'     => $stage['is_lost'] ?? false,
            ]);

            // Required tasks vindas do PipelineTemplates (fallback inteligente)
            foreach (($stage['required_tasks'] ?? []) as $taskI => $task) {
                StageRequiredTask::create([
                    'pipeline_stage_id' => $createdStage->id,
                    'subject'           => $task['subject'],
                    'description'       => $task['description'] ?? null,
                    'task_type'         => $task['task_type'] ?? 'task',
                    'priority'          => $task['priority'] ?? 'medium',
                    'due_date_offset'   => $task['due_date_offset'] ?? 0,
                    'sort_order'        => $taskI + 1,
                ]);
            }
        }

        return [
            'name'         => $pipeline->name,
            'stages_count' => count($data['stages']),
            'stages'       => array_column($data['stages'], 'name'),
        ];
    }

    /**
     * Cria pipeline a partir de um template do PipelineTemplates.
     * Inclui stages + required_tasks já curados pela equipe.
     */
    private function createPipelineFromTemplate(string $slug): array
    {
        $template = PipelineTemplates::find($slug);
        if (! $template) {
            // Slug inválido — cai no fallback genérico
            Log::warning('GenerateCRMFromAI: pipeline template slug not found, using fallback', [
                'slug'      => $slug,
                'tenant_id' => $this->tenant->id,
            ]);
            return $this->createPipeline($this->getFallbackData()['pipeline']);
        }

        $pipeline = Pipeline::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => $template['name'],
            'color'      => $template['color'] ?? '#0085f3',
            'is_default' => true,
            'sort_order' => 1,
        ]);

        foreach ($template['stages'] as $i => $stage) {
            $createdStage = PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name'        => $stage['name'],
                'color'       => $stage['color'] ?? '#6B7280',
                'position'    => $i + 1,
                'is_won'      => $stage['is_won'] ?? false,
                'is_lost'     => $stage['is_lost'] ?? false,
            ]);

            foreach (($stage['required_tasks'] ?? []) as $taskI => $task) {
                StageRequiredTask::create([
                    'pipeline_stage_id' => $createdStage->id,
                    'subject'           => $task['subject'],
                    'description'       => $task['description'] ?? null,
                    'task_type'         => $task['task_type'] ?? 'task',
                    'priority'          => $task['priority'] ?? 'medium',
                    'due_date_offset'   => $task['due_date_offset'] ?? 0,
                    'sort_order'        => $taskI + 1,
                ]);
            }
        }

        return [
            'name'             => $pipeline->name,
            'stages_count'     => count($template['stages']),
            'stages'           => array_column($template['stages'], 'name'),
            'template_slug'    => $slug,
        ];
    }

    private function createSequences(array $sequences): array
    {
        $result = [];

        foreach ($sequences as $seqData) {
            $seq = NurtureSequence::create([
                'tenant_id'            => $this->tenant->id,
                'name'                 => $seqData['name'] ?? 'Sequence',
                'description'          => $seqData['description'] ?? null,
                'is_active'            => true,
                'exit_on_reply'        => true,
                'exit_on_stage_change' => false,
            ]);

            foreach (($seqData['steps'] ?? []) as $i => $step) {
                NurtureSequenceStep::create([
                    'sequence_id'   => $seq->id,
                    'position'      => $i + 1,
                    'delay_minutes' => $step['delay_minutes'] ?? 0,
                    'type'          => $step['type'] ?? 'message',
                    'config'        => $step['config'] ?? ['body' => ''],
                ]);
            }

            $result[] = ['name' => $seq->name, 'steps' => count($seqData['steps'] ?? [])];
        }

        return $result;
    }

    private function createAutomations(array $automations): array
    {
        $result = [];

        foreach ($automations as $auto) {
            Automation::create([
                'tenant_id'    => $this->tenant->id,
                'name'         => $auto['name'] ?? 'Automation',
                'is_active'    => true,
                'trigger_type' => $auto['trigger_type'] ?? 'message_received',
                'trigger_config' => $auto['trigger_config'] ?? [],
                'conditions'   => $auto['conditions'] ?? [],
                'actions'      => $auto['actions'] ?? [],
            ]);

            $result[] = ['name' => $auto['name'] ?? 'Automation'];
        }

        return $result;
    }

    private function createScoringRules(array $rules): array
    {
        $result = [];

        foreach ($rules as $i => $rule) {
            ScoringRule::create([
                'tenant_id'      => $this->tenant->id,
                'name'           => $rule['name'] ?? 'Rule',
                'category'       => $rule['category'] ?? 'engagement',
                'event_type'     => $rule['event_type'] ?? 'message_received',
                'points'         => $rule['points'] ?? 5,
                'is_active'      => true,
                'cooldown_hours' => $rule['cooldown_hours'] ?? 0,
                'sort_order'     => $i,
            ]);

            $result[] = ['name' => $rule['name'], 'points' => $rule['points'] ?? 5];
        }

        return $result;
    }

    private function createAiAgent(array $data): array
    {
        if (empty($data['name'])) {
            return ['name' => null];
        }

        AiAgent::create([
            'tenant_id'              => $this->tenant->id,
            'name'                   => $data['name'],
            'objective'              => $data['objective'] ?? '',
            'persona_description'    => $data['persona_description'] ?? '',
            'communication_style'    => $data['communication_style'] ?? 'friendly',
            'knowledge_base'         => $data['knowledge_base'] ?? '',
            'is_active'              => true,
            'enable_pipeline_tool'   => true,
            'enable_tags_tool'       => true,
            'enable_intent_notify'   => true,
        ]);

        return ['name' => $data['name']];
    }

    private function createQuickMessages(array $messages): array
    {
        $result = [];

        foreach ($messages as $i => $msg) {
            WhatsappQuickMessage::create([
                'tenant_id'  => $this->tenant->id,
                'title'      => $msg['title'] ?? 'Message',
                'body'       => $msg['body'] ?? '',
                'sort_order' => $i + 1,
            ]);

            $result[] = ['title' => $msg['title']];
        }

        return $result;
    }

    private function createTags(array $tags): void
    {
        $colors = ['#0085f3', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];

        foreach ($tags as $i => $tagName) {
            WhatsappTag::create([
                'tenant_id'  => $this->tenant->id,
                'name'       => $tagName,
                'color'      => $colors[$i % count($colors)],
                'sort_order' => $i + 1,
            ]);
        }
    }

    private function createLossReasons(array $reasons): void
    {
        foreach ($reasons as $i => $reason) {
            LostSaleReason::create([
                'tenant_id'  => $this->tenant->id,
                'name'       => $reason,
                'sort_order' => $i + 1,
                'is_active'  => true,
            ]);
        }
    }
}
