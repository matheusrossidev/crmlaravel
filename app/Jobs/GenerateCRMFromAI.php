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
use App\Models\Tenant;
use App\Models\WhatsappQuickMessage;
use App\Models\WhatsappTag;
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

    public function __construct(
        private Tenant $tenant,
        private array $answers,
    ) {}

    public function handle(): void
    {
        $cacheKey = "onboarding:progress:{$this->tenant->id}";

        try {
            // Step 1: Analyzing
            $this->markStep($cacheKey, 'analyzing');

            $json = $this->callOpenAI();

            if (! $json) {
                // Fallback to templates if AI fails
                $json = $this->getFallbackData();
            }

            // Step 2: Pipeline
            $this->markStep($cacheKey, 'pipeline');
            $pipelineResult = $this->createPipeline($json['pipeline'] ?? []);

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
            ];

            Cache::put("onboarding:result:{$this->tenant->id}", $resultData, 3600);
            Cache::put($cacheKey, [
                'status'    => 'done',
                'completed' => ['analyzing', 'pipeline', 'sequences', 'automations', 'scoring', 'ai_agent', 'quick_messages', 'config'],
                'total'     => 8,
                'error'     => null,
            ], 600);

        } catch (\Throwable $e) {
            Log::error('GenerateCRMFromAI failed', [
                'tenant_id' => $this->tenant->id,
                'error'     => $e->getMessage(),
            ]);

            Cache::put($cacheKey, [
                'status'    => 'error',
                'completed' => Cache::get($cacheKey)['completed'] ?? [],
                'total'     => 8,
                'error'     => $e->getMessage(),
            ], 600);

            // Mark onboarding as done even on failure (user can configure manually)
            $this->tenant->update(['onboarding_completed_at' => now()]);
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

        return <<<PROMPT
Generate a complete CRM setup for this business. All text content MUST be in {$lang}.

BUSINESS CONTEXT:
- Industry: {$a['niche']}
- Channels: {$this->formatArray($a['channels'])}
- Sales process described by user: "{$a['sales_process']}"
- Biggest challenge: {$a['difficulty']}
- Team size: {$a['team_size']}

Return a JSON object with this EXACT structure:

{
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
- Pipeline must have 4-6 stages + 1 won + 1 lost stage
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

    private function getFallbackData(): array
    {
        $isEn = ($this->answers['locale'] ?? 'pt_BR') === 'en';

        return [
            'pipeline' => [
                'name'   => $isEn ? 'Sales Pipeline' : 'Funil de Vendas',
                'stages' => [
                    ['name' => $isEn ? 'New Lead' : 'Novo Lead', 'color' => '#6B7280'],
                    ['name' => $isEn ? 'Contacted' : 'Em Contato', 'color' => '#3B82F6'],
                    ['name' => $isEn ? 'Proposal' : 'Proposta', 'color' => '#F59E0B'],
                    ['name' => $isEn ? 'Negotiation' : 'Negociação', 'color' => '#8B5CF6'],
                    ['name' => $isEn ? 'Won' : 'Ganho', 'color' => '#10B981', 'is_won' => true],
                    ['name' => $isEn ? 'Lost' : 'Perdido', 'color' => '#EF4444', 'is_lost' => true],
                ],
            ],
            'sequences'      => [],
            'automations'    => [],
            'scoring_rules'  => [
                ['name' => $isEn ? 'Message received' : 'Mensagem recebida', 'category' => 'engagement', 'event_type' => 'message_received', 'points' => 5, 'cooldown_hours' => 1],
                ['name' => $isEn ? 'Stage advanced' : 'Avançou de etapa', 'category' => 'pipeline', 'event_type' => 'stage_advanced', 'points' => 15, 'cooldown_hours' => 0],
            ],
            'ai_agent'       => [],
            'quick_messages' => [],
            'tags'           => $isEn
                ? ['Hot', 'Warm', 'Cold', 'Priority', 'Follow-up']
                : ['Quente', 'Morno', 'Frio', 'Prioritário', 'Retorno'],
            'loss_reasons'   => $isEn
                ? ['Too expensive', 'No interest', 'Chose competitor', 'No response', 'Bad timing']
                : ['Preço alto', 'Sem interesse', 'Optou por concorrente', 'Sem retorno', 'Timing errado'],
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
            PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name'        => $stage['name'],
                'color'       => $stage['color'] ?? '#6B7280',
                'position'    => $i + 1,
                'is_won'      => $stage['is_won'] ?? false,
                'is_lost'     => $stage['is_lost'] ?? false,
            ]);
        }

        return [
            'name'         => $pipeline->name,
            'stages_count' => count($data['stages']),
            'stages'       => array_column($data['stages'], 'name'),
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
