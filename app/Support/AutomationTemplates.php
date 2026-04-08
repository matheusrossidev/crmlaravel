<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Biblioteca de templates de automações prontas para diferentes nichos.
 *
 * Mesmo padrão do PipelineTemplates / ScoringRuleTemplates: hardcoded em PHP,
 * defaults pt_BR inline, i18n via lang/<locale>/automation_templates.php.
 *
 * Cada template tem:
 *  - slug, category, name, description, icon
 *  - automation: array compatível com Automation::create()
 *      - name, trigger_type, trigger_config, conditions, actions
 *
 * Trigger types suportados (ver AutomationController validation):
 *  - message_received, conversation_created, lead_created,
 *    lead_stage_changed, lead_won, lead_lost, date_field, recurring
 *
 * Action types disponíveis (ver AutomationEngine::executeAction):
 *  - add_tag_lead, remove_tag_lead, add_tag_conversation, move_to_stage,
 *    set_lead_source, assign_to_user, add_note, assign_ai_agent,
 *    assign_chatbot_flow, close_conversation, send_whatsapp_message,
 *    schedule_whatsapp_message, set_utm_params,
 *    transfer_to_department, create_task, enroll_sequence,
 *    ai_extract_fields, send_webhook
 *
 * Foco: 9 nichos PME (mesmos do ScoringRuleTemplates).
 */
final class AutomationTemplates
{
    /**
     * @return array<string,string>
     */
    public static function categories(): array
    {
        $pt = [
            'imobiliaria'       => 'Imobiliária',
            'saude'             => 'Saúde',
            'ecommerce'         => 'E-commerce',
            'educacao'          => 'Educação',
            'beleza'            => 'Beleza & Estética',
            'b2b_servicos'      => 'Serviços B2B',
            'agencia_marketing' => 'Marketing & Agência',
            'saas_tech'         => 'SaaS & Tech',
            'b2c_varejo'        => 'B2C / Varejo',
        ];
        return self::applyCategoryOverrides($pt);
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $template) {
            if ($template['slug'] === $slug) {
                return $template;
            }
        }
        return null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function all(): array
    {
        return self::applyTemplateOverrides(self::ptTemplates());
    }

    /**
     * @return list<array<string,mixed>>
     */
    private static function ptTemplates(): array
    {
        return [
            // ── IMOBILIÁRIA ──────────────────────────────────────────────
            [
                'slug'        => 'imo_boas_vindas_wpp',
                'category'    => 'imobiliaria',
                'name'        => 'Boas-vindas WhatsApp',
                'description' => 'Quando um novo lead chega via WhatsApp, envia mensagem de boas-vindas e cria tarefa de qualificação imediata.',
                'icon'        => 'bi-hand-thumbs-up',
                'automation' => [
                    'name'           => 'Boas-vindas WhatsApp',
                    'trigger_type'   => 'lead_created',
                    'trigger_config' => null,
                    'conditions'     => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Novo Lead']]],
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Olá {{nome}}! Recebemos seu contato e em instantes nosso corretor entrará em contato com você. 🏠']],
                        ['type' => 'create_task', 'config' => [
                            'subject'         => 'Qualificar lead novo',
                            'task_type'       => 'call',
                            'priority'        => 'high',
                            'due_date_offset' => 0,
                        ]],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_visita_agendada',
                'category'    => 'imobiliaria',
                'name'        => 'Confirmar visita agendada',
                'description' => 'Quando o lead avança para a etapa "Visita Agendada", cria tarefa de confirmação 1 dia antes.',
                'icon'        => 'bi-calendar-check',
                'automation' => [
                    'name'           => 'Confirmar visita agendada',
                    'trigger_type'   => 'lead_stage_changed',
                    'trigger_config' => null,
                    'conditions'     => null,
                    'actions' => [
                        ['type' => 'create_task', 'config' => [
                            'subject'         => 'Confirmar visita 1 dia antes',
                            'task_type'       => 'whatsapp',
                            'priority'        => 'high',
                            'due_date_offset' => 1,
                        ]],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_venda_fechada',
                'category'    => 'imobiliaria',
                'name'        => 'Venda fechada → marcar cliente',
                'description' => 'Quando o negócio é ganho, adiciona tag "Cliente" e transfere para o departamento de pós-venda.',
                'icon'        => 'bi-trophy',
                'automation' => [
                    'name'           => 'Venda fechada',
                    'trigger_type'   => 'lead_won',
                    'trigger_config' => null,
                    'conditions'     => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Cliente']]],
                        ['type' => 'create_task', 'config' => [
                            'subject'         => 'Iniciar processo de pós-venda',
                            'task_type'       => 'task',
                            'priority'        => 'medium',
                            'due_date_offset' => 1,
                        ]],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_recorrencia_imoveis_dia5',
                'category'    => 'imobiliaria',
                'name'        => 'Recorrência mensal: Imóveis disponíveis',
                'description' => 'Todo dia 5 de cada mês, envia uma mensagem com os imóveis disponíveis para todos os leads ativos. Use {{nome}} para personalizar.',
                'icon'        => 'bi-arrow-repeat',
                'automation' => [
                    'name'           => 'Recorrência mensal — Imóveis disponíveis',
                    'trigger_type'   => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly',
                        'days'            => [5],
                        'time'            => '09:00',
                        'filter_type'     => 'all',
                        'filter_value'    => null,
                        'daily_limit'     => 100,
                        'delay_seconds'   => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! Esses são os imóveis novos do mês. Quer dar uma olhada? 🏡']],
                    ],
                ],
            ],

            // ── SAÚDE ────────────────────────────────────────────────────
            [
                'slug' => 'sau_boas_vindas', 'category' => 'saude',
                'name' => 'Boas-vindas paciente novo',
                'description' => 'Mensagem inicial + tarefa pra equipe entrar em contato em 30 minutos.',
                'icon' => 'bi-heart-pulse',
                'automation' => [
                    'name' => 'Boas-vindas paciente', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Olá {{nome}}! Recebemos seu contato. Em instantes uma de nossas atendentes vai te responder. 💙']],
                        ['type' => 'create_task', 'config' => ['subject' => 'Atender paciente novo', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'sau_consulta_realizada', 'category' => 'saude',
                'name' => 'Pós-consulta — pedir review',
                'description' => 'Após ganho da venda, marca como cliente e cria tarefa de pedir avaliação.',
                'icon' => 'bi-star',
                'automation' => [
                    'name' => 'Pós-consulta review', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Paciente']]],
                        ['type' => 'create_task', 'config' => ['subject' => 'Pedir avaliação Google', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1]],
                    ],
                ],
            ],
            [
                'slug' => 'sau_aniversario', 'category' => 'saude',
                'name' => 'Aniversário do paciente',
                'description' => 'Envia parabéns automático em dias de aniversário (precisa custom field birthday).',
                'icon' => 'bi-gift',
                'automation' => [
                    'name' => 'Parabéns aniversário', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => true],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Parabéns, {{nome}}! 🎂 Que esse novo ciclo seja repleto de saúde. Equipe [Sua Empresa].']],
                    ],
                ],
            ],
            [
                'slug' => 'sau_recorrencia_checkup', 'category' => 'saude',
                'name' => 'Recorrência mensal: lembrete de check-up',
                'description' => 'Todo dia 1 de cada mês, lembra pacientes ativos sobre check-up de rotina.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência mensal — Check-up', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly', 'days' => [1], 'time' => '10:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 100, 'delay_seconds' => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, está na hora do seu check-up de rotina. 🏥 Quer agendar sua consulta?']],
                    ],
                ],
            ],

            // ── E-COMMERCE ───────────────────────────────────────────────
            [
                'slug' => 'eco_boas_vindas_cupom', 'category' => 'ecommerce',
                'name' => 'Boas-vindas + cupom de desconto',
                'description' => 'Cliente novo recebe cupom imediatamente após cadastro.',
                'icon' => 'bi-ticket-perforated',
                'automation' => [
                    'name' => 'Boas-vindas + cupom', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Bem-vindo(a), {{nome}}! 🎁 Use o cupom BEMVINDO10 e ganhe 10% OFF na sua primeira compra.']],
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Novo Cadastro']]],
                    ],
                ],
            ],
            [
                'slug' => 'eco_carrinho_abandonado', 'category' => 'ecommerce',
                'name' => 'Recuperação de carrinho',
                'description' => 'Quando lead retrocede de etapa (abandona), agenda mensagem de recuperação em 1 dia.',
                'icon' => 'bi-cart',
                'automation' => [
                    'name' => 'Recuperação carrinho', 'trigger_type' => 'lead_stage_changed',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'schedule_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! Você esqueceu alguns produtos no carrinho. Quer finalizar a compra com 5% OFF? 🛒', 'delay_value' => 1, 'delay_unit' => 'days']],
                    ],
                ],
            ],
            [
                'slug' => 'eco_cliente_vip', 'category' => 'ecommerce',
                'name' => 'Cliente VIP — venda fechada',
                'description' => 'Quando cliente compra, marca como cliente e agenda follow-up de cross-sell.',
                'icon' => 'bi-trophy',
                'automation' => [
                    'name' => 'Cliente VIP', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Cliente']]],
                        ['type' => 'schedule_whatsapp_message', 'config' => ['message' => '{{nome}}, esperamos que esteja amando seu produto! ✨ Veja outras opções que combinam com você.', 'delay_value' => 7, 'delay_unit' => 'days']],
                    ],
                ],
            ],
            [
                'slug' => 'eco_recorrencia_ofertas', 'category' => 'ecommerce',
                'name' => 'Recorrência semanal: ofertas',
                'description' => 'Toda sexta às 10h envia ofertas da semana para clientes ativos.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência semanal — Ofertas', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'weekly', 'days' => [5], 'time' => '10:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 200, 'delay_seconds' => 6,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Sextou, {{nome}}! 🎉 Confira nossas ofertas especiais da semana com até 30% OFF.']],
                    ],
                ],
            ],

            // ── EDUCAÇÃO ─────────────────────────────────────────────────
            [
                'slug' => 'edu_conteudo_gratuito', 'category' => 'educacao',
                'name' => 'Lead novo → conteúdo gratuito',
                'description' => 'Envia material gratuito imediatamente ao cadastro.',
                'icon' => 'bi-book',
                'automation' => [
                    'name' => 'Conteúdo gratuito', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Olá {{nome}}! 📚 Aqui está o material gratuito que prometemos. Confira nossos cursos!']],
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Lead Educacional']]],
                    ],
                ],
            ],
            [
                'slug' => 'edu_visualizou_preco', 'category' => 'educacao',
                'name' => 'Avançou para "Negociação" → criar tarefa',
                'description' => 'Quando lead chega na negociação, cria tarefa de ligar.',
                'icon' => 'bi-tag',
                'automation' => [
                    'name' => 'Negociação ativa', 'trigger_type' => 'lead_stage_changed',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'create_task', 'config' => ['subject' => 'Ligar para fechar matrícula', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'edu_matriculado', 'category' => 'educacao',
                'name' => 'Matriculado → onboarding',
                'description' => 'Quando aluno é matriculado, marca como aluno e cria tarefa de onboarding.',
                'icon' => 'bi-mortarboard',
                'automation' => [
                    'name' => 'Onboarding aluno', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Aluno']]],
                        ['type' => 'create_task', 'config' => ['subject' => 'Iniciar onboarding do aluno', 'task_type' => 'task', 'priority' => 'medium', 'due_date_offset' => 1]],
                    ],
                ],
            ],
            [
                'slug' => 'edu_recorrencia_cursos', 'category' => 'educacao',
                'name' => 'Recorrência mensal: novos cursos',
                'description' => 'Todo dia 10 envia novos cursos para todos os leads.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência mensal — Novos cursos', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly', 'days' => [10], 'time' => '14:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 100, 'delay_seconds' => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, novos cursos chegaram! 📚 Quer conhecer as novidades do mês?']],
                    ],
                ],
            ],

            // ── BELEZA & ESTÉTICA ────────────────────────────────────────
            [
                'slug' => 'bel_confirmacao_agendamento', 'category' => 'beleza',
                'name' => 'Confirmação de agendamento',
                'description' => 'Aniversário do agendamento — envia lembrete D-1.',
                'icon' => 'bi-calendar-check',
                'automation' => [
                    'name' => 'Confirmação agendamento', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => false],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! 💄 Lembrando do seu agendamento amanhã. Qualquer dúvida, é só chamar.']],
                    ],
                ],
            ],
            [
                'slug' => 'bel_aniversario_desconto', 'category' => 'beleza',
                'name' => 'Aniversário com desconto',
                'description' => 'Envia desconto especial no aniversário do cliente.',
                'icon' => 'bi-gift',
                'automation' => [
                    'name' => 'Aniversário desconto', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => true],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Parabéns, {{nome}}! 🎂 Como presente, 20% OFF no seu próximo agendamento. Válido até o final do mês.']],
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Aniversariante']]],
                    ],
                ],
            ],
            [
                'slug' => 'bel_cliente_vip', 'category' => 'beleza',
                'name' => 'Cliente VIP automático',
                'description' => 'Marca como VIP quando ganha venda (após várias visitas).',
                'icon' => 'bi-star',
                'automation' => [
                    'name' => 'Cliente VIP', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['VIP']]],
                    ],
                ],
            ],
            [
                'slug' => 'bel_recorrencia_promocao', 'category' => 'beleza',
                'name' => 'Recorrência mensal: promoção do mês',
                'description' => 'Todo dia 15 envia a promoção do mês para todos os clientes.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência mensal — Promoção', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly', 'days' => [15], 'time' => '11:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 100, 'delay_seconds' => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, a promoção do mês chegou! 💅 Confira os serviços com até 25% OFF.']],
                    ],
                ],
            ],

            // ── B2B SERVIÇOS ─────────────────────────────────────────────
            [
                'slug' => 'b2b_lead_company', 'category' => 'b2b_servicos',
                'name' => 'Lead B2B — marcar empresa',
                'description' => 'Quando novo lead chega, adiciona tag "B2B" e cria tarefa de qualificação.',
                'icon' => 'bi-building',
                'automation' => [
                    'name' => 'Lead B2B', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['B2B']]],
                        ['type' => 'create_task', 'config' => ['subject' => 'Qualificar lead B2B', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'b2b_demo_agendada', 'category' => 'b2b_servicos',
                'name' => 'Demo agendada → enviar deck',
                'description' => 'Quando lead avança pra demo, envia material e cria tarefa.',
                'icon' => 'bi-camera-video',
                'automation' => [
                    'name' => 'Demo agendada', 'trigger_type' => 'lead_stage_changed',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Olá {{nome}}! 📊 Aqui está nosso deck de apresentação. Nos vemos na demo!']],
                        ['type' => 'create_task', 'config' => ['subject' => 'Preparar demo personalizada', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'b2b_ganho_onboarding', 'category' => 'b2b_servicos',
                'name' => 'Ganho → onboarding cliente',
                'description' => 'Marca como cliente e cria tarefa de kickoff.',
                'icon' => 'bi-trophy',
                'automation' => [
                    'name' => 'Onboarding cliente B2B', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Cliente']]],
                        ['type' => 'create_task', 'config' => ['subject' => 'Agendar reunião de kickoff', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 1]],
                    ],
                ],
            ],
            [
                'slug' => 'b2b_recorrencia_newsletter', 'category' => 'b2b_servicos',
                'name' => 'Recorrência mensal: newsletter B2B',
                'description' => 'Todo dia 20 envia newsletter com conteúdo de valor.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência mensal — Newsletter B2B', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly', 'days' => [20], 'time' => '09:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 100, 'delay_seconds' => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, nossa newsletter B2B chegou! 📰 Confira os principais insights do mês.']],
                    ],
                ],
            ],

            // ── MARKETING & AGÊNCIA ──────────────────────────────────────
            [
                'slug' => 'agm_lead_site', 'category' => 'agencia_marketing',
                'name' => 'Lead com site → tarefa de auditoria',
                'description' => 'Quando lead chega, cria tarefa para auditar o site dele.',
                'icon' => 'bi-globe',
                'automation' => [
                    'name' => 'Auditar site do lead', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'create_task', 'config' => ['subject' => 'Auditar site do lead', 'task_type' => 'task', 'priority' => 'medium', 'due_date_offset' => 1]],
                    ],
                ],
            ],
            [
                'slug' => 'agm_cotacao_followup', 'category' => 'agencia_marketing',
                'name' => 'Cotação enviada → follow-up D+2',
                'description' => 'Após avançar pra cotação, agenda follow-up em 2 dias.',
                'icon' => 'bi-clock',
                'automation' => [
                    'name' => 'Follow-up cotação', 'trigger_type' => 'lead_stage_changed',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'schedule_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! Conseguiu dar uma olhada na nossa proposta? Qualquer dúvida, estou aqui.', 'delay_value' => 2, 'delay_unit' => 'days']],
                    ],
                ],
            ],
            [
                'slug' => 'agm_ganho_kickoff', 'category' => 'agencia_marketing',
                'name' => 'Ganho → kickoff',
                'description' => 'Cliente fechado, agenda kickoff e marca tag.',
                'icon' => 'bi-rocket-takeoff',
                'automation' => [
                    'name' => 'Kickoff cliente', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Cliente Ativo']]],
                        ['type' => 'create_task', 'config' => ['subject' => 'Agendar reunião de kickoff', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 2]],
                    ],
                ],
            ],
            [
                'slug' => 'agm_recorrencia_cases', 'category' => 'agencia_marketing',
                'name' => 'Recorrência mensal: cases de sucesso',
                'description' => 'Todo dia 25 envia cases de sucesso para leads em negociação.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência mensal — Cases', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'monthly', 'days' => [25], 'time' => '14:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 100, 'delay_seconds' => 8,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, olha esses cases de sucesso que rolaram esse mês! 🚀 Você pode ser o próximo.']],
                    ],
                ],
            ],

            // ── SAAS & TECH ──────────────────────────────────────────────
            [
                'slug' => 'saas_trial_onboarding', 'category' => 'saas_tech',
                'name' => 'Trial → onboarding',
                'description' => 'Lead novo recebe email de onboarding e marca como trial.',
                'icon' => 'bi-rocket-takeoff',
                'automation' => [
                    'name' => 'Trial onboarding', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Trial']]],
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Bem-vindo ao [Sua SaaS], {{nome}}! 🚀 Aqui está seu guia rápido pra começar.']],
                    ],
                ],
            ],
            [
                'slug' => 'saas_trial_50_call', 'category' => 'saas_tech',
                'name' => 'Trial 50% → call de ativação',
                'description' => 'No meio do trial, cria tarefa de call para ajudar a ativar.',
                'icon' => 'bi-telephone',
                'automation' => [
                    'name' => 'Trial 50% call', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => false],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'create_task', 'config' => ['subject' => 'Call de ativação do trial', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'saas_trial_80_offer', 'category' => 'saas_tech',
                'name' => 'Trial 80% → oferta upgrade',
                'description' => 'Próximo do fim do trial, envia oferta de conversão.',
                'icon' => 'bi-gift',
                'automation' => [
                    'name' => 'Trial 80% oferta', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => false],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, seu trial está acabando! 💎 Use o cupom UPGRADE20 e ganhe 20% OFF no plano anual.']],
                    ],
                ],
            ],
            [
                'slug' => 'saas_churn_recuperacao', 'category' => 'saas_tech',
                'name' => 'Trial expirou → recuperação',
                'description' => 'Quando trial expira sem conversão, cria tarefa de recuperação.',
                'icon' => 'bi-arrow-counterclockwise',
                'automation' => [
                    'name' => 'Recuperação churn', 'trigger_type' => 'lead_lost',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'create_task', 'config' => ['subject' => 'Tentar recuperar trial expirado', 'task_type' => 'call', 'priority' => 'medium', 'due_date_offset' => 2]],
                    ],
                ],
            ],

            // ── B2C / VAREJO ─────────────────────────────────────────────
            [
                'slug' => 'b2c_resposta_automatica', 'category' => 'b2c_varejo',
                'name' => 'Lead novo → resposta automática + tarefa 30min',
                'description' => 'Resposta imediata + tarefa de ligar em 30 minutos.',
                'icon' => 'bi-lightning',
                'automation' => [
                    'name' => 'Resposta automática B2C', 'trigger_type' => 'lead_created',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! Recebemos sua mensagem e em poucos minutos vamos te responder. ⚡']],
                        ['type' => 'create_task', 'config' => ['subject' => 'Atender lead em 30min', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0]],
                    ],
                ],
            ],
            [
                'slug' => 'b2c_compra_crosssell', 'category' => 'b2c_varejo',
                'name' => 'Comprou → cross-sell',
                'description' => 'Após compra, agenda mensagem de cross-sell em 7 dias.',
                'icon' => 'bi-bag-plus',
                'automation' => [
                    'name' => 'Cross-sell pós-compra', 'trigger_type' => 'lead_won',
                    'trigger_config' => null, 'conditions' => null,
                    'actions' => [
                        ['type' => 'add_tag_lead', 'config' => ['tags' => ['Cliente']]],
                        ['type' => 'schedule_whatsapp_message', 'config' => ['message' => 'Oi {{nome}}! 🛍️ Tem novidade que combina com o que você comprou. Quer dar uma olhada?', 'delay_value' => 7, 'delay_unit' => 'days']],
                    ],
                ],
            ],
            [
                'slug' => 'b2c_aniversario_cupom', 'category' => 'b2c_varejo',
                'name' => 'Aniversário → cupom',
                'description' => 'Envia cupom de desconto especial no aniversário.',
                'icon' => 'bi-gift',
                'automation' => [
                    'name' => 'Aniversário cupom', 'trigger_type' => 'date_field',
                    'trigger_config' => ['date_field' => 'birthday', 'repeat_yearly' => true],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => 'Parabéns, {{nome}}! 🎂 Use o cupom NIVER15 e ganhe 15% OFF no seu mês de aniversário.']],
                    ],
                ],
            ],
            [
                'slug' => 'b2c_recorrencia_ofertas', 'category' => 'b2c_varejo',
                'name' => 'Recorrência semanal: ofertas',
                'description' => 'Toda quinta envia ofertas semanais para clientes.',
                'icon' => 'bi-arrow-repeat',
                'automation' => [
                    'name' => 'Recorrência semanal — Ofertas B2C', 'trigger_type' => 'recurring',
                    'trigger_config' => [
                        'recurrence_type' => 'weekly', 'days' => [4], 'time' => '11:00',
                        'filter_type' => 'all', 'filter_value' => null,
                        'daily_limit' => 200, 'delay_seconds' => 6,
                    ],
                    'conditions' => null,
                    'actions' => [
                        ['type' => 'send_whatsapp_message', 'config' => ['message' => '{{nome}}, ofertas da semana chegaram! 🛒 Confira os destaques.']],
                    ],
                ],
            ],
        ];
    }

    // ── i18n helpers (mesmo padrão do PipelineTemplates) ────────────────────

    private static function applyCategoryOverrides(array $defaults): array
    {
        $overrides = self::loadTranslations()['categories'] ?? null;
        if (! is_array($overrides)) {
            return $defaults;
        }
        foreach ($overrides as $key => $label) {
            if (isset($defaults[$key]) && is_string($label) && $label !== '') {
                $defaults[$key] = $label;
            }
        }
        return $defaults;
    }

    /**
     * @param  list<array<string,mixed>>  $templates
     * @return list<array<string,mixed>>
     */
    private static function applyTemplateOverrides(array $templates): array
    {
        $overrides = self::loadTranslations()['templates'] ?? null;
        if (! is_array($overrides)) {
            return $templates;
        }

        foreach ($templates as &$template) {
            $slug = $template['slug'] ?? null;
            if (! $slug || ! isset($overrides[$slug]) || ! is_array($overrides[$slug])) {
                continue;
            }
            $tr = $overrides[$slug];

            if (isset($tr['name']) && is_string($tr['name']) && $tr['name'] !== '') {
                $template['name'] = $tr['name'];
            }
            if (isset($tr['description']) && is_string($tr['description']) && $tr['description'] !== '') {
                $template['description'] = $tr['description'];
            }
            if (isset($tr['automation']['name']) && is_string($tr['automation']['name']) && $tr['automation']['name'] !== '') {
                $template['automation']['name'] = $tr['automation']['name'];
            }
            // Sobrescrever mensagens dentro de actions[*].config.message se houver tradução
            if (isset($tr['automation']['actions']) && is_array($tr['automation']['actions'])) {
                foreach ($template['automation']['actions'] as $actionIdx => &$action) {
                    if (isset($tr['automation']['actions'][$actionIdx]['message']) && isset($action['config']['message'])) {
                        $action['config']['message'] = (string) $tr['automation']['actions'][$actionIdx]['message'];
                    }
                    if (isset($tr['automation']['actions'][$actionIdx]['subject']) && isset($action['config']['subject'])) {
                        $action['config']['subject'] = (string) $tr['automation']['actions'][$actionIdx]['subject'];
                    }
                }
                unset($action);
            }
        }
        unset($template);

        return $templates;
    }

    /**
     * @return array<string,mixed>
     */
    private static function loadTranslations(): array
    {
        static $cache = [];

        $locale = app()->getLocale();
        if ($locale === 'pt_BR' || $locale === 'pt-BR' || $locale === 'pt') {
            return [];
        }
        if (array_key_exists($locale, $cache)) {
            return $cache[$locale];
        }

        $path = lang_path("{$locale}/automation_templates.php");
        if (! is_file($path)) {
            return $cache[$locale] = [];
        }

        $data = require $path;
        return $cache[$locale] = is_array($data) ? $data : [];
    }
}
