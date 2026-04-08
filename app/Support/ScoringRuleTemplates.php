<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Biblioteca de templates de regras de Lead Scoring prontas para diferentes nichos.
 *
 * Mesmo padrão arquitetural do PipelineTemplates: hardcoded em PHP, defaults
 * em pt_BR inline. Strings traduzíveis (name, description) são sobrescritas
 * por loadTranslations() quando o locale ativo é diferente de pt_BR.
 *
 * Cada template tem:
 *  - slug (único)
 *  - category (chave em categories())
 *  - name, description, icon
 *  - rule[] com a estrutura compatível com ScoringRule::create()
 *
 * Estrutura de `rule`:
 *  - name: string
 *  - category: 'engagement' | 'pipeline' | 'profile'
 *  - event_type: string (ver lang/pt_BR/scoring.php → evt_*)
 *  - points: int (-100 a 100)
 *  - cooldown_hours: int (0..720)
 *  - conditions: ?array (formato matchesConditions do LeadScoringService)
 *
 * Foco: 9 nichos PME (imobiliaria, saude, ecommerce, educacao, beleza,
 * b2b_servicos, agencia_marketing, saas_tech, b2c_varejo).
 */
final class ScoringRuleTemplates
{
    /**
     * @return array<string,string> slug => label PT (sobrescrito por locale)
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
                'slug'        => 'imo_lead_respondeu',
                'category'    => 'imobiliaria',
                'name'        => 'Lead respondeu mensagem',
                'description' => 'Pontua leads que demonstram interesse respondendo qualquer mensagem.',
                'icon'        => 'bi-chat-dots',
                'rule' => [
                    'name'           => 'Lead respondeu mensagem',
                    'category'       => 'engagement',
                    'event_type'     => 'message_received',
                    'points'         => 5,
                    'cooldown_hours' => 24,
                    'conditions'     => null,
                ],
            ],
            [
                'slug'        => 'imo_perfil_completo',
                'category'    => 'imobiliaria',
                'name'        => 'Email + telefone preenchidos',
                'description' => 'Premia leads que forneceram email e telefone — sinal forte de interesse.',
                'icon'        => 'bi-person-check',
                'rule' => [
                    'name'           => 'Perfil completo',
                    'category'       => 'profile',
                    'event_type'     => 'profile_complete',
                    'points'         => 10,
                    'cooldown_hours' => 0,
                    'conditions'     => [
                        ['field' => 'has_email', 'operator' => 'equals', 'value' => true],
                        ['field' => 'has_phone', 'operator' => 'equals', 'value' => true],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_midia_enviada',
                'category'    => 'imobiliaria',
                'name'        => 'Lead enviou mídia (foto/vídeo)',
                'description' => 'Lead que envia foto do imóvel desejado ou vídeo demonstra alto interesse.',
                'icon'        => 'bi-image',
                'rule' => [
                    'name'           => 'Mídia enviada pelo lead',
                    'category'       => 'engagement',
                    'event_type'     => 'message_sent_media',
                    'points'         => 8,
                    'cooldown_hours' => 12,
                    'conditions'     => null,
                ],
            ],
            [
                'slug'        => 'imo_avancou_visita',
                'category'    => 'imobiliaria',
                'name'        => 'Avançou para etapa de visita',
                'description' => 'Marca um pico de interesse — visita agendada é um dos maiores indicadores de fechamento.',
                'icon'        => 'bi-arrow-up-right',
                'rule' => [
                    'name'           => 'Avançou de etapa',
                    'category'       => 'pipeline',
                    'event_type'     => 'stage_advanced',
                    'points'         => 15,
                    'cooldown_hours' => 0,
                    'conditions'     => null,
                ],
            ],
            [
                'slug'        => 'imo_inativo_7d',
                'category'    => 'imobiliaria',
                'name'        => 'Lead inativo há 7 dias',
                'description' => 'Penaliza leads sem contato há mais de uma semana — esfriamento natural.',
                'icon'        => 'bi-snow',
                'rule' => [
                    'name'           => 'Inativo 7 dias',
                    'category'       => 'engagement',
                    'event_type'     => 'inactive_7d',
                    'points'         => -5,
                    'cooldown_hours' => 168, // 7 dias
                    'conditions'     => null,
                ],
            ],

            // ── SAÚDE ────────────────────────────────────────────────────
            [
                'slug' => 'sau_msg_queixa', 'category' => 'saude',
                'name' => 'Mensagem com queixa específica',
                'description' => 'Lead que descreve sintomas ou queixa demonstra interesse alto em consulta.',
                'icon' => 'bi-clipboard-pulse',
                'rule' => [
                    'name' => 'Mensagem com queixa', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 8, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'sau_agendou_consulta', 'category' => 'saude',
                'name' => 'Agendou consulta',
                'description' => 'Maior indicador de conversão — paciente confirmou data e horário.',
                'icon' => 'bi-calendar-check',
                'rule' => [
                    'name' => 'Agendou consulta', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'sau_resposta_rapida', 'category' => 'saude',
                'name' => 'Resposta rápida (< 5 min)',
                'description' => 'Paciente respondendo rapidamente indica urgência e interesse alto.',
                'icon' => 'bi-lightning',
                'rule' => [
                    'name' => 'Resposta rápida', 'category' => 'engagement',
                    'event_type' => 'fast_reply', 'points' => 10, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'sau_perfil_completo', 'category' => 'saude',
                'name' => 'Perfil completo (email + telefone)',
                'description' => 'Paciente forneceu dados completos — sinal de comprometimento.',
                'icon' => 'bi-person-check',
                'rule' => [
                    'name' => 'Perfil completo', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 5, 'cooldown_hours' => 0,
                    'conditions' => [
                        ['field' => 'has_email', 'operator' => 'equals', 'value' => true],
                        ['field' => 'has_phone', 'operator' => 'equals', 'value' => true],
                    ],
                ],
            ],
            [
                'slug' => 'sau_no_show', 'category' => 'saude',
                'name' => 'No-show (faltou consulta)',
                'description' => 'Penaliza pacientes que não compareceram à consulta agendada.',
                'icon' => 'bi-x-circle',
                'rule' => [
                    'name' => 'No-show', 'category' => 'pipeline',
                    'event_type' => 'lead_lost', 'points' => -10, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],

            // ── E-COMMERCE ───────────────────────────────────────────────
            [
                'slug' => 'eco_lead_respondeu', 'category' => 'ecommerce',
                'name' => 'Lead respondeu',
                'description' => 'Lead engajado que respondeu mensagem da loja.',
                'icon' => 'bi-chat-dots',
                'rule' => [
                    'name' => 'Lead respondeu', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 5, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'eco_valor_alto', 'category' => 'ecommerce',
                'name' => 'Valor estimado > R$500',
                'description' => 'Lead interessado em produtos de ticket médio-alto.',
                'icon' => 'bi-cash-stack',
                'rule' => [
                    'name' => 'Ticket médio-alto', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 10, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'lead_value', 'operator' => 'gte', 'value' => 500]],
                ],
            ],
            [
                'slug' => 'eco_compra_realizada', 'category' => 'ecommerce',
                'name' => 'Compra realizada',
                'description' => 'Maior pontuação — cliente efetivou a compra.',
                'icon' => 'bi-bag-check',
                'rule' => [
                    'name' => 'Compra realizada', 'category' => 'pipeline',
                    'event_type' => 'lead_won', 'points' => 20, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'eco_carrinho_abandonado', 'category' => 'ecommerce',
                'name' => 'Carrinho abandonado (retrocedeu)',
                'description' => 'Lead que retrocedeu de etapa indicando abandono de carrinho.',
                'icon' => 'bi-cart-x',
                'rule' => [
                    'name' => 'Carrinho abandonado', 'category' => 'pipeline',
                    'event_type' => 'stage_regressed', 'points' => -5, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'eco_inativo_30d', 'category' => 'ecommerce',
                'name' => 'Inativo há 30 dias',
                'description' => 'Decay para clientes que não interagem há um mês.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo 30d', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -10, 'cooldown_hours' => 720, 'conditions' => null,
                ],
            ],

            // ── EDUCAÇÃO ─────────────────────────────────────────────────
            [
                'slug' => 'edu_lead_novo', 'category' => 'educacao',
                'name' => 'Novo lead interessado',
                'description' => 'Pontuação base para todo lead que entra no funil.',
                'icon' => 'bi-person-plus',
                'rule' => [
                    'name' => 'Novo lead', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 3, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'edu_perfil_completo', 'category' => 'educacao',
                'name' => 'Perfil completo (email + telefone)',
                'description' => 'Aluno forneceu dados completos — alta intenção de matrícula.',
                'icon' => 'bi-person-check',
                'rule' => [
                    'name' => 'Perfil completo', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 8, 'cooldown_hours' => 0,
                    'conditions' => [
                        ['field' => 'has_email', 'operator' => 'equals', 'value' => true],
                        ['field' => 'has_phone', 'operator' => 'equals', 'value' => true],
                    ],
                ],
            ],
            [
                'slug' => 'edu_avancou_matricula', 'category' => 'educacao',
                'name' => 'Avançou para matrícula',
                'description' => 'Aluno chegou na etapa de matrícula — alto sinal de conversão.',
                'icon' => 'bi-mortarboard',
                'rule' => [
                    'name' => 'Avançou para matrícula', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'edu_resposta_rapida', 'category' => 'educacao',
                'name' => 'Resposta rápida (< 5 min)',
                'description' => 'Alta intenção quando o aluno responde rápido.',
                'icon' => 'bi-lightning',
                'rule' => [
                    'name' => 'Resposta rápida', 'category' => 'engagement',
                    'event_type' => 'fast_reply', 'points' => 10, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'edu_inativo_14d', 'category' => 'educacao',
                'name' => 'Inativo há 14 dias',
                'description' => 'Decay para alunos sem interação há 2 semanas.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo 14d', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -5, 'cooldown_hours' => 336, 'conditions' => null,
                ],
            ],

            // ── BELEZA & ESTÉTICA ────────────────────────────────────────
            [
                'slug' => 'bel_agendou', 'category' => 'beleza',
                'name' => 'Agendou serviço',
                'description' => 'Cliente agendou serviço — conversão direta.',
                'icon' => 'bi-calendar-check',
                'rule' => [
                    'name' => 'Agendou serviço', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 10, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'bel_recorrente', 'category' => 'beleza',
                'name' => 'Cliente recorrente (3+ visitas)',
                'description' => 'Identificação de cliente fidelizado para programa VIP.',
                'icon' => 'bi-star',
                'rule' => [
                    'name' => 'Cliente recorrente', 'category' => 'profile',
                    'event_type' => 'lead_won', 'points' => 15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'bel_indicacao', 'category' => 'beleza',
                'name' => 'Lead via indicação',
                'description' => 'Lead que veio por indicação tem maior taxa de conversão.',
                'icon' => 'bi-heart',
                'rule' => [
                    'name' => 'Lead indicado', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 12, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'lead_source', 'operator' => 'equals', 'value' => 'indicacao']],
                ],
            ],
            [
                'slug' => 'bel_no_show', 'category' => 'beleza',
                'name' => 'No-show (faltou)',
                'description' => 'Penaliza clientes que não compareceram ao agendamento.',
                'icon' => 'bi-x-circle',
                'rule' => [
                    'name' => 'No-show', 'category' => 'pipeline',
                    'event_type' => 'lead_lost', 'points' => -10, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'bel_inativo_60d', 'category' => 'beleza',
                'name' => 'Inativo há 60 dias',
                'description' => 'Decay para clientes que não voltaram em 2 meses.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo 60d', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -5, 'cooldown_hours' => 1440, 'conditions' => null,
                ],
            ],

            // ── B2B SERVIÇOS ─────────────────────────────────────────────
            [
                'slug' => 'b2b_company_preenchida', 'category' => 'b2b_servicos',
                'name' => 'Empresa identificada',
                'description' => 'Lead com nome de empresa preenchido = venda B2B real.',
                'icon' => 'bi-building',
                'rule' => [
                    'name' => 'Empresa preenchida', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 10, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'has_company', 'operator' => 'equals', 'value' => true]],
                ],
            ],
            [
                'slug' => 'b2b_valor_alto', 'category' => 'b2b_servicos',
                'name' => 'Valor estimado > R$5k',
                'description' => 'Deal de ticket alto — atribui prioridade automática.',
                'icon' => 'bi-currency-dollar',
                'rule' => [
                    'name' => 'Ticket alto B2B', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 15, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'lead_value', 'operator' => 'gte', 'value' => 5000]],
                ],
            ],
            [
                'slug' => 'b2b_demo_agendada', 'category' => 'b2b_servicos',
                'name' => 'Demo agendada',
                'description' => 'Maior indicador de intenção em B2B — demo confirmada.',
                'icon' => 'bi-camera-video',
                'rule' => [
                    'name' => 'Demo agendada', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 20, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'b2b_msg_orcamento', 'category' => 'b2b_servicos',
                'name' => 'Mensagem com "orçamento"',
                'description' => 'Lead pediu orçamento explicitamente — prontidão para fechar.',
                'icon' => 'bi-receipt',
                'rule' => [
                    'name' => 'Pediu orçamento', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 8, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'b2b_resposta_rapida', 'category' => 'b2b_servicos',
                'name' => 'Resposta rápida',
                'description' => 'Lead respondendo rápido em B2B é fortíssimo indicador.',
                'icon' => 'bi-lightning',
                'rule' => [
                    'name' => 'Resposta rápida B2B', 'category' => 'engagement',
                    'event_type' => 'fast_reply', 'points' => 12, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],

            // ── MARKETING & AGÊNCIA ──────────────────────────────────────
            [
                'slug' => 'agm_lead_company', 'category' => 'agencia_marketing',
                'name' => 'Lead com empresa identificada',
                'description' => 'Empresa identificada — base para cotação personalizada.',
                'icon' => 'bi-building',
                'rule' => [
                    'name' => 'Empresa identificada', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 8, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'has_company', 'operator' => 'equals', 'value' => true]],
                ],
            ],
            [
                'slug' => 'agm_cotacao_enviada', 'category' => 'agencia_marketing',
                'name' => 'Cotação enviada',
                'description' => 'Sinal de avanço — cotação no funil.',
                'icon' => 'bi-file-earmark-text',
                'rule' => [
                    'name' => 'Cotação enviada', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 10, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'agm_negociacao', 'category' => 'agencia_marketing',
                'name' => 'Avançou para negociação',
                'description' => 'Indicador forte — está discutindo termos.',
                'icon' => 'bi-handshake',
                'rule' => [
                    'name' => 'Negociação ativa', 'category' => 'pipeline',
                    'event_type' => 'stage_advanced', 'points' => 15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'agm_resposta_rapida', 'category' => 'agencia_marketing',
                'name' => 'Resposta rápida',
                'description' => 'Decisor respondendo rápido = atenção alta.',
                'icon' => 'bi-lightning',
                'rule' => [
                    'name' => 'Resposta rápida', 'category' => 'engagement',
                    'event_type' => 'fast_reply', 'points' => 8, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'agm_inativo_14d', 'category' => 'agencia_marketing',
                'name' => 'Inativo 14 dias',
                'description' => 'Decay — agências têm ciclo curto-médio, 14d sem resposta é frio.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo 14d', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -5, 'cooldown_hours' => 336, 'conditions' => null,
                ],
            ],

            // ── SAAS & TECH ──────────────────────────────────────────────
            [
                'slug' => 'saas_signup_trial', 'category' => 'saas_tech',
                'name' => 'Sign-up no trial',
                'description' => 'Lead criou conta — passo zero da jornada.',
                'icon' => 'bi-rocket-takeoff',
                'rule' => [
                    'name' => 'Trial signup', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 10, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'saas_ativou_feature', 'category' => 'saas_tech',
                'name' => 'Ativou feature chave',
                'description' => 'User explorou a feature principal — sinal forte de conversão.',
                'icon' => 'bi-check2-square',
                'rule' => [
                    'name' => 'Ativou feature chave', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 15, 'cooldown_hours' => 168, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'saas_convidou_colega', 'category' => 'saas_tech',
                'name' => 'Convidou colega de equipe',
                'description' => 'Convite a outro user = expansion potencial.',
                'icon' => 'bi-people',
                'rule' => [
                    'name' => 'Convidou equipe', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 12, 'cooldown_hours' => 168, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'saas_inativo_trial', 'category' => 'saas_tech',
                'name' => 'Inativo no trial',
                'description' => 'Decay para trials que param de usar.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo trial', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -10, 'cooldown_hours' => 168, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'saas_trial_expirou', 'category' => 'saas_tech',
                'name' => 'Trial expirou sem conversão',
                'description' => 'Indica churn — penaliza fortemente o score.',
                'icon' => 'bi-x-octagon',
                'rule' => [
                    'name' => 'Trial expirou', 'category' => 'pipeline',
                    'event_type' => 'lead_lost', 'points' => -15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],

            // ── B2C / VAREJO ─────────────────────────────────────────────
            [
                'slug' => 'b2c_lead_respondeu', 'category' => 'b2c_varejo',
                'name' => 'Lead respondeu',
                'description' => 'Engajamento básico — cliente respondeu.',
                'icon' => 'bi-chat-dots',
                'rule' => [
                    'name' => 'Lead respondeu', 'category' => 'engagement',
                    'event_type' => 'message_received', 'points' => 5, 'cooldown_hours' => 24, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'b2c_valor_preenchido', 'category' => 'b2c_varejo',
                'name' => 'Valor estimado preenchido',
                'description' => 'Lead informou valor — qualificação inicial OK.',
                'icon' => 'bi-tag',
                'rule' => [
                    'name' => 'Valor preenchido', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 8, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'lead_value', 'operator' => 'gt', 'value' => 0]],
                ],
            ],
            [
                'slug' => 'b2c_compra', 'category' => 'b2c_varejo',
                'name' => 'Comprou',
                'description' => 'Conversão — cliente efetuou compra.',
                'icon' => 'bi-bag-check',
                'rule' => [
                    'name' => 'Compra realizada', 'category' => 'pipeline',
                    'event_type' => 'lead_won', 'points' => 15, 'cooldown_hours' => 0, 'conditions' => null,
                ],
            ],
            [
                'slug' => 'b2c_ticket_alto', 'category' => 'b2c_varejo',
                'name' => 'Ticket alto (> R$1k)',
                'description' => 'Cliente premium — atenção dedicada.',
                'icon' => 'bi-gem',
                'rule' => [
                    'name' => 'Ticket alto', 'category' => 'profile',
                    'event_type' => 'profile_complete', 'points' => 10, 'cooldown_hours' => 0,
                    'conditions' => [['field' => 'lead_value', 'operator' => 'gte', 'value' => 1000]],
                ],
            ],
            [
                'slug' => 'b2c_inativo_60d', 'category' => 'b2c_varejo',
                'name' => 'Inativo 60 dias',
                'description' => 'Decay para clientes B2C que somem por 2 meses.',
                'icon' => 'bi-snow',
                'rule' => [
                    'name' => 'Inativo 60d B2C', 'category' => 'engagement',
                    'event_type' => 'inactive_7d', 'points' => -10, 'cooldown_hours' => 1440, 'conditions' => null,
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
            if (isset($tr['rule']['name']) && is_string($tr['rule']['name']) && $tr['rule']['name'] !== '') {
                $template['rule']['name'] = $tr['rule']['name'];
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

        $path = lang_path("{$locale}/scoring_rule_templates.php");
        if (! is_file($path)) {
            return $cache[$locale] = [];
        }

        $data = require $path;
        return $cache[$locale] = is_array($data) ? $data : [];
    }
}
