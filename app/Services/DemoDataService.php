<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Department;
use App\Models\Lead;
use App\Models\NpsSurvey;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesGoal;
use App\Models\ScoringRule;
use App\Models\SurveyResponse;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataService
{
    private int $tenantId;
    private array $lines = [];

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function generateAll(): array
    {
        DB::beginTransaction();
        try {
            $this->createUsers();
            $this->createDepartments();
            $this->createCustomFields();
            $this->createProducts();
            $this->createScoringRules();
            $this->createNurtureSequence();
            $this->createNpsSurvey();
            $this->createSalesGoals();
            $this->createAutomations();
            $this->createChatbotFlow();
            $this->createAiAgent();
            $this->createTasks();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->lines[] = "[ERRO] {$e->getMessage()}";
        }

        return $this->lines;
    }

    private function createUsers(): void
    {
        $existing = User::where('tenant_id', $this->tenantId)->count();
        if ($existing >= 3) {
            $this->lines[] = 'Usuários: já existem ' . $existing . ' (pulando)';
            return;
        }

        $demoUsers = [
            ['name' => 'Carlos Gestor',    'email' => "gestor.demo.{$this->tenantId}@syncro.test", 'role' => 'manager'],
            ['name' => 'Maria Vendedora',  'email' => "vendedora.demo.{$this->tenantId}@syncro.test", 'role' => 'manager'],
            ['name' => 'Pedro Viewer',     'email' => "viewer.demo.{$this->tenantId}@syncro.test", 'role' => 'viewer'],
        ];

        $count = 0;
        foreach ($demoUsers as $u) {
            if (User::where('email', $u['email'])->exists()) continue;
            User::create([
                'tenant_id'         => $this->tenantId,
                'name'              => $u['name'],
                'email'             => $u['email'],
                'password'          => 'password',
                'role'              => $u['role'],
                'email_verified_at' => now(),
            ]);
            $count++;
        }
        $this->lines[] = "Usuários fictícios criados: {$count}";
    }

    private function createDepartments(): void
    {
        if (Department::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Departamentos: já existem (pulando)';
            return;
        }

        $depts = [
            ['name' => 'Vendas',     'icon' => 'cash-stack',    'color' => '#10b981', 'assignment_strategy' => 'round_robin'],
            ['name' => 'Suporte',    'icon' => 'headset',       'color' => '#0085f3', 'assignment_strategy' => 'least_busy'],
            ['name' => 'Financeiro', 'icon' => 'bank',          'color' => '#f59e0b', 'assignment_strategy' => 'round_robin'],
        ];

        foreach ($depts as $d) {
            Department::withoutGlobalScope('tenant')->create(array_merge($d, [
                'tenant_id' => $this->tenantId,
                'is_active' => true,
            ]));
        }
        $this->lines[] = 'Departamentos criados: ' . count($depts);
    }

    private function createCustomFields(): void
    {
        if (CustomFieldDefinition::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Campos personalizados: já existem (pulando)';
            return;
        }

        $fields = [
            ['name' => 'cargo',        'label' => 'Cargo',         'field_type' => 'text',       'sort_order' => 1],
            ['name' => 'faturamento',   'label' => 'Faturamento',   'field_type' => 'currency',   'sort_order' => 2],
            ['name' => 'segmento',      'label' => 'Segmento',      'field_type' => 'select',     'sort_order' => 3, 'options_json' => ['Tecnologia', 'Saúde', 'Educação', 'Varejo', 'Serviços', 'Indústria']],
            ['name' => 'qtd_funcionarios', 'label' => 'Qtd. Funcionários', 'field_type' => 'number', 'sort_order' => 4],
            ['name' => 'data_reuniao',  'label' => 'Data da Reunião', 'field_type' => 'date',     'sort_order' => 5],
        ];

        foreach ($fields as $f) {
            CustomFieldDefinition::withoutGlobalScope('tenant')->create(array_merge($f, [
                'tenant_id' => $this->tenantId,
                'is_active' => true,
                'show_on_card' => true,
            ]));
        }
        $this->lines[] = 'Campos personalizados criados: ' . count($fields);
    }

    private function createProducts(): void
    {
        if (Product::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Produtos: já existem (pulando)';
            return;
        }

        // Categories
        $cat1 = ProductCategory::create(['name' => 'Planos', 'sort_order' => 1]);
        $cat2 = ProductCategory::create(['name' => 'Serviços', 'sort_order' => 2]);
        $cat3 = ProductCategory::create(['name' => 'Add-ons', 'sort_order' => 3]);

        $products = [
            ['name' => 'Plano Starter',    'price' => 97,    'cost_price' => 20,  'category_id' => $cat1->id, 'sku' => 'PLN-STARTER'],
            ['name' => 'Plano Pro',         'price' => 197,   'cost_price' => 40,  'category_id' => $cat1->id, 'sku' => 'PLN-PRO'],
            ['name' => 'Plano Enterprise',  'price' => 497,   'cost_price' => 100, 'category_id' => $cat1->id, 'sku' => 'PLN-ENT'],
            ['name' => 'Setup Inicial',     'price' => 500,   'cost_price' => 150, 'category_id' => $cat2->id, 'sku' => 'SVC-SETUP'],
            ['name' => 'Consultoria/hora',  'price' => 150,   'cost_price' => 50,  'category_id' => $cat2->id, 'sku' => 'SVC-HORA'],
            ['name' => 'Pacote 50k Tokens', 'price' => 49,    'cost_price' => 10,  'category_id' => $cat3->id, 'sku' => 'ADD-TOKEN'],
            ['name' => 'Instância WA Extra','price' => 79,    'cost_price' => 15,  'category_id' => $cat3->id, 'sku' => 'ADD-WA'],
        ];

        foreach ($products as $p) {
            Product::withoutGlobalScope('tenant')->create(array_merge($p, [
                'tenant_id' => $this->tenantId,
                'is_active' => true,
            ]));
        }
        $this->lines[] = 'Categorias criadas: 3 | Produtos criados: ' . count($products);
    }

    private function createScoringRules(): void
    {
        if (ScoringRule::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Scoring rules: já existem (pulando)';
            return;
        }

        $rules = [
            ['name' => 'Mensagem recebida',   'event_type' => 'message_received',  'points' => 5,   'category' => 'engagement'],
            ['name' => 'Email informado',      'event_type' => 'field_filled',      'points' => 15,  'category' => 'profile',    'conditions' => ['field' => 'email']],
            ['name' => 'Etapa avançada',       'event_type' => 'stage_changed',     'points' => 20,  'category' => 'pipeline'],
            ['name' => 'Lead criado',          'event_type' => 'lead_created',      'points' => 10,  'category' => 'engagement'],
            ['name' => 'Sem resposta 3 dias',  'event_type' => 'no_reply_3d',       'points' => -10, 'category' => 'decay'],
            ['name' => 'Sem resposta 7 dias',  'event_type' => 'no_reply_7d',       'points' => -20, 'category' => 'decay'],
        ];

        foreach ($rules as $r) {
            ScoringRule::withoutGlobalScope('tenant')->create(array_merge($r, [
                'tenant_id' => $this->tenantId,
                'is_active' => true,
                'cooldown_hours' => 24,
                'sort_order' => 0,
                'conditions' => $r['conditions'] ?? [],
            ]));
        }
        $this->lines[] = 'Scoring rules criadas: ' . count($rules);
    }

    private function createNurtureSequence(): void
    {
        if (NurtureSequence::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Nurture sequences: já existem (pulando)';
            return;
        }

        $seq = NurtureSequence::withoutGlobalScope('tenant')->create([
            'tenant_id'            => $this->tenantId,
            'name'                 => 'Boas-vindas (Demo)',
            'description'          => 'Sequência automática de boas-vindas para novos leads',
            'is_active'            => true,
            'channel'              => 'whatsapp',
            'exit_on_reply'        => true,
            'exit_on_stage_change' => false,
        ]);

        $steps = [
            ['position' => 1, 'delay_minutes' => 0,    'type' => 'message', 'config' => ['text' => 'Olá! Obrigado pelo seu interesse. Como posso ajudar?']],
            ['position' => 2, 'delay_minutes' => 1440,  'type' => 'message', 'config' => ['text' => 'Vi que você demonstrou interesse nos nossos serviços. Posso te ajudar com alguma dúvida?']],
            ['position' => 3, 'delay_minutes' => 4320,  'type' => 'message', 'config' => ['text' => 'Última mensagem! Estou à disposição caso queira saber mais. 😊']],
        ];

        foreach ($steps as $s) {
            NurtureSequenceStep::create(array_merge($s, ['sequence_id' => $seq->id, 'is_active' => true]));
        }
        $this->lines[] = 'Nurture sequence criada: ' . $seq->name . ' (3 steps)';
    }

    private function createNpsSurvey(): void
    {
        if (NpsSurvey::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'NPS surveys: já existem (pulando)';
            return;
        }

        $survey = NpsSurvey::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenantId,
            'name'               => 'Pesquisa de Satisfação (Demo)',
            'type'               => 'nps',
            'question'           => 'De 0 a 10, o quanto você recomendaria nossa empresa?',
            'follow_up_question' => 'O que podemos melhorar?',
            'trigger'            => 'manual',
            'send_via'           => 'whatsapp',
            'is_active'          => true,
            'slug'               => 'demo-nps-' . $this->tenantId,
            'thank_you_message'  => 'Obrigado pela sua avaliação! Sua opinião é muito importante para nós.',
        ]);

        // Fake responses
        $scores = [10, 9, 8, 10, 7, 9, 6, 10, 8, 9, 4, 10, 8, 3, 9];
        $comments = ['Excelente atendimento!', 'Muito bom', 'Poderia ser mais rápido', 'Top!', '', 'Ótimo serviço', '', 'Recomendo!', 'Bom', 'Precisa melhorar o suporte', '', 'Nota 10!', '', 'Demora no retorno', 'Fantástico'];

        $leads = Lead::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->inRandomOrder()->limit(count($scores))->pluck('id');

        foreach ($scores as $i => $score) {
            SurveyResponse::withoutGlobalScope('tenant')->create([
                'uuid'       => (string) Str::uuid(),
                'tenant_id'  => $this->tenantId,
                'survey_id'  => $survey->id,
                'lead_id'    => $leads[$i] ?? null,
                'score'      => $score,
                'comment'    => $comments[$i] ?? null,
                'status'     => 'answered',
                'sent_at'    => now()->subDays(rand(1, 30)),
                'answered_at'=> now()->subDays(rand(0, 29)),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
        $this->lines[] = 'NPS survey criada com ' . count($scores) . ' respostas (NPS score calculável)';
    }

    private function createSalesGoals(): void
    {
        if (SalesGoal::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Metas: já existem (pulando)';
            return;
        }

        $users = User::where('tenant_id', $this->tenantId)->pluck('id')->toArray();
        $admin = User::where('tenant_id', $this->tenantId)->where('role', 'admin')->first();

        // Meta da equipe
        SalesGoal::withoutGlobalScope('tenant')->create([
            'tenant_id'    => $this->tenantId,
            'user_id'      => null,
            'type'         => 'sales_value',
            'period'       => 'monthly',
            'target_value' => 100000,
            'start_date'   => now()->startOfMonth(),
            'end_date'     => now()->endOfMonth(),
            'created_by'   => $admin?->id ?? $users[0] ?? null,
            'is_recurring' => true,
            'growth_rate'  => 5.0,
        ]);

        // Meta individual
        foreach (array_slice($users, 0, 3) as $userId) {
            SalesGoal::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $this->tenantId,
                'user_id'      => $userId,
                'type'         => 'sales_count',
                'period'       => 'monthly',
                'target_value' => rand(5, 15),
                'start_date'   => now()->startOfMonth(),
                'end_date'     => now()->endOfMonth(),
                'created_by'   => $admin?->id ?? $users[0] ?? null,
                'is_recurring' => true,
                'growth_rate'  => 0,
            ]);
        }
        $this->lines[] = 'Metas criadas: 1 equipe + ' . min(3, count($users)) . ' individuais';
    }

    private function createAutomations(): void
    {
        if (Automation::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Automações: já existem (pulando)';
            return;
        }

        $automations = [
            [
                'name'        => 'Tag VIP ao fechar venda',
                'trigger_type'=> 'stage_changed',
                'trigger_config' => ['to_stage_type' => 'won'],
                'actions'     => [['type' => 'add_tag', 'value' => 'VIP']],
            ],
            [
                'name'        => 'Notificar gestor em lead de alto valor',
                'trigger_type'=> 'lead_created',
                'conditions'  => [['field' => 'value', 'operator' => 'gte', 'value' => 10000]],
                'actions'     => [['type' => 'notify_user', 'message' => 'Lead de alto valor criado!']],
            ],
        ];

        foreach ($automations as $a) {
            Automation::withoutGlobalScope('tenant')->create(array_merge($a, [
                'tenant_id' => $this->tenantId,
                'is_active' => true,
                'conditions' => $a['conditions'] ?? [],
                'trigger_config' => $a['trigger_config'] ?? [],
            ]));
        }
        $this->lines[] = 'Automações criadas: ' . count($automations);
    }

    private function createChatbotFlow(): void
    {
        if (ChatbotFlow::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Chatbot flows: já existem (pulando)';
            return;
        }

        $flow = ChatbotFlow::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenantId,
            'name'             => 'Atendimento Inicial (Demo)',
            'channel'          => 'whatsapp',
            'is_active'        => false,
            'trigger_keywords' => ['oi', 'olá', 'bom dia', 'boa tarde'],
            'variables'        => [['name' => 'nome', 'default' => ''], ['name' => 'interesse', 'default' => '']],
        ]);

        // Nós
        $n1 = ChatbotFlowNode::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'type' => 'message', 'config' => ['text' => 'Olá! 👋 Bem-vindo ao nosso atendimento!'],
            'canvas_x' => 100, 'canvas_y' => 100,
        ]);
        $n2 = ChatbotFlowNode::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'type' => 'input', 'config' => ['text' => 'Qual é o seu nome?', 'save_to' => 'nome', 'field_type' => 'name'],
            'canvas_x' => 100, 'canvas_y' => 250,
        ]);
        $n3 = ChatbotFlowNode::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'type' => 'input', 'config' => [
                'text' => 'Como posso te ajudar?',
                'save_to' => 'interesse',
                'input_type' => 'buttons',
                'branches' => [
                    ['handle' => 'branch-0', 'label' => 'Conhecer planos', 'keywords' => ['planos', 'preço']],
                    ['handle' => 'branch-1', 'label' => 'Suporte', 'keywords' => ['suporte', 'ajuda']],
                    ['handle' => 'branch-2', 'label' => 'Falar com humano', 'keywords' => ['humano', 'atendente']],
                ],
            ],
            'canvas_x' => 100, 'canvas_y' => 400,
        ]);
        $n4 = ChatbotFlowNode::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'type' => 'action', 'config' => ['type' => 'assign_human'],
            'canvas_x' => 100, 'canvas_y' => 550,
        ]);
        $n5 = ChatbotFlowNode::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'type' => 'end', 'config' => ['text' => 'Obrigado! Em breve um atendente vai falar com você. 😊'],
            'canvas_x' => 100, 'canvas_y' => 700,
        ]);

        // Edges
        foreach ([[$n1->id, $n2->id], [$n2->id, $n3->id], [$n4->id, $n5->id]] as [$src, $tgt]) {
            ChatbotFlowEdge::withoutGlobalScope('tenant')->create([
                'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
                'source_node_id' => $src, 'target_node_id' => $tgt, 'source_handle' => 'default',
            ]);
        }
        // Branch edges
        ChatbotFlowEdge::withoutGlobalScope('tenant')->create([
            'flow_id' => $flow->id, 'tenant_id' => $this->tenantId,
            'source_node_id' => $n3->id, 'target_node_id' => $n4->id, 'source_handle' => 'branch-2',
        ]);

        $this->lines[] = 'Chatbot flow criado: ' . $flow->name . ' (5 nós)';
    }

    private function createAiAgent(): void
    {
        if (AiAgent::withoutGlobalScope('tenant')->where('tenant_id', $this->tenantId)->exists()) {
            $this->lines[] = 'Agentes IA: já existem (pulando)';
            return;
        }

        AiAgent::withoutGlobalScope('tenant')->create([
            'tenant_id'              => $this->tenantId,
            'name'                   => 'Assistente Demo',
            'objective'              => 'sales',
            'communication_style'    => 'normal',
            'persona_description'    => 'Você é um assistente de vendas da empresa. Sua missão é entender as necessidades do cliente e ajudá-lo.',
            'language'               => 'pt-BR',
            'knowledge_base'         => "Somos uma empresa de tecnologia.\nNossos planos: Starter (R$97/mês), Pro (R$197/mês), Enterprise (R$497/mês).\nHorário de atendimento: seg-sex 9h-18h.",
            'is_active'              => false,
            'enable_pipeline_tool'   => true,
            'enable_tags_tool'       => true,
            'enable_calendar_tool'   => false,
            'use_agno'               => true,
            'response_delay_seconds' => 3,
            'response_wait_seconds'  => 8,
        ]);
        $this->lines[] = 'Agente IA criado: Assistente Demo (inativo)';
    }

    private function createTasks(): void
    {
        $leads = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'name']);

        if ($leads->isEmpty()) {
            $this->lines[] = 'Tarefas: sem leads para vincular (pulando)';
            return;
        }

        $users = User::where('tenant_id', $this->tenantId)->pluck('id')->toArray();
        $types = ['call', 'email', 'task', 'visit', 'whatsapp', 'meeting'];
        $subjects = [
            'call'     => ['Ligar para follow-up', 'Retorno de ligação', 'Ligar para apresentar proposta'],
            'email'    => ['Enviar proposta comercial', 'Enviar material de apresentação', 'Enviar contrato'],
            'task'     => ['Preparar apresentação', 'Atualizar CRM', 'Analisar concorrência'],
            'visit'    => ['Visita técnica', 'Reunião presencial', 'Visita de prospecção'],
            'whatsapp' => ['Enviar mensagem de follow-up', 'Confirmar horário', 'Enviar link de pagamento'],
            'meeting'  => ['Reunião de qualificação', 'Demo do produto', 'Reunião de fechamento'],
        ];
        $priorities = ['low', 'medium', 'high'];

        $count = 0;
        foreach ($leads as $lead) {
            $type    = $types[array_rand($types)];
            $subject = $subjects[$type][array_rand($subjects[$type])];
            $dueDate = now()->addDays(rand(-5, 14));
            $isCompleted = $dueDate->isPast() && rand(1, 100) <= 60;

            Task::withoutGlobalScope('tenant')->create([
                'tenant_id'   => $this->tenantId,
                'subject'     => $subject,
                'type'        => $type,
                'status'      => $isCompleted ? 'completed' : 'pending',
                'priority'    => $priorities[array_rand($priorities)],
                'due_date'    => $dueDate->format('Y-m-d'),
                'completed_at'=> $isCompleted ? $dueDate : null,
                'lead_id'     => $lead->id,
                'assigned_to' => !empty($users) ? $users[array_rand($users)] : null,
                'created_by'  => !empty($users) ? $users[array_rand($users)] : null,
            ]);
            $count++;
        }
        $this->lines[] = "Tarefas criadas: {$count}";
    }
}
