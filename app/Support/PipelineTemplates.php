<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Biblioteca de templates de funis prontos para diferentes nichos.
 *
 * Templates são hardcoded (sem migration/seed) — manutenção em código é mais
 * simples e os templates raramente mudam. Cada template tem:
 *
 * - slug (único)
 * - category (chave em categories())
 * - name, color, icon, description
 * - stages[] com {name, color, is_won, is_lost, required_tasks[]}
 * - required_tasks[] com {subject, task_type, priority, due_date_offset}
 *
 * task_type: call|email|task|visit|whatsapp|meeting
 * priority: low|medium|high
 * due_date_offset: dias a partir da entrada na etapa (0..365)
 */
final class PipelineTemplates
{
    /**
     * @return array<string,string> slug => label
     */
    public static function categories(): array
    {
        $pt = [
            'imobiliaria'        => 'Imobiliária',
            'saude'              => 'Saúde',
            'educacao'           => 'Educação',
            'restaurante_food'   => 'Restaurante & Food',
            'ecommerce'          => 'E-commerce',
            'servicos_b2b'       => 'Serviços B2B',
            'marketing_agencia'  => 'Marketing & Agência',
            'beleza_estetica'    => 'Beleza & Estética',
            'automotivo'         => 'Automotivo',
            'advocacia'          => 'Advocacia',
            'tecnologia_saas'    => 'Tecnologia & SaaS',
            'coach_consultoria'  => 'Coach & Consultoria',
            'eventos'            => 'Eventos',
            'construcao_reforma' => 'Construção & Reforma',
            'turismo'            => 'Turismo',
            'fitness'            => 'Fitness',
            'financeiro'         => 'Financeiro',
            'recursos_humanos'   => 'Recursos Humanos',
            'pet'                => 'Pet',
            'religioso'          => 'Religioso',
            'vendas_b2c'         => 'Vendas B2C',
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
     * Templates em português (defaults). Strings traduzíveis são sobrescritas
     * por applyTemplateOverrides() quando o locale ativo tem traduções.
     *
     * @return list<array<string,mixed>>
     */
    private static function ptTemplates(): array
    {
        return [
            // ── IMOBILIÁRIA ──────────────────────────────────────────────
            [
                'slug' => 'imobiliaria_locacao',
                'category' => 'imobiliaria',
                'name' => 'Locação Residencial',
                'icon' => 'bi-house-door',
                'color' => '#3B82F6',
                'description' => 'Funil padrão para captação e fechamento de locações de imóveis residenciais',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Ligar para qualificar interesse e orçamento', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Imóveis Selecionados', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar opções de imóveis pelo WhatsApp', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Visita Agendada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar visita 1 dia antes', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                        ['subject' => 'Realizar visita ao imóvel', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Documentação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Solicitar documentos do locatário', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 1],
                        ['subject' => 'Enviar para análise de crédito', 'task_type' => 'task', 'priority' => 'medium', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Contrato Assinado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Aprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'imobiliaria_venda',
                'category' => 'imobiliaria',
                'name' => 'Venda Residencial',
                'icon' => 'bi-house-heart',
                'color' => '#2563EB',
                'description' => 'Captação, qualificação financeira e fechamento de venda de imóveis',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Primeiro contato e qualificação de perfil', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Pré-aprovação Financeira', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Solicitar simulação no banco', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Visitas Realizadas', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Agendar e fazer visita aos imóveis', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Proposta Apresentada', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Negociar valores e condições', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Financiamento Aprovado', 'color' => '#FB923C', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Conferir documentação completa', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Escritura Lavrada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'imobiliaria_lancamento',
                'category' => 'imobiliaria',
                'name' => 'Lançamento de Empreendimento',
                'icon' => 'bi-buildings',
                'color' => '#1D4ED8',
                'description' => 'Captação de leads e venda de unidades em pré-lançamento de empreendimentos',
                'stages' => [
                    ['name' => 'Lead Cadastrado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar material do empreendimento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Interesse Confirmado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Agendar visita ao stand', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Visita ao Stand', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar planta e diferenciais', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Reserva de Unidade', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar sinal e dados', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Vendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistiu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'imobiliaria_comercial',
                'category' => 'imobiliaria',
                'name' => 'Comercial / Galpão',
                'icon' => 'bi-shop',
                'color' => '#1E40AF',
                'description' => 'Locação e venda de imóveis comerciais, salas e galpões',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender necessidades do negócio', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Imóveis Sugeridos', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar opções compatíveis', 'task_type' => 'email', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Vistoria Técnica', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Agendar visita técnica ao imóvel', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 4],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Negociar valor, prazo e condições', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Fechado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── SAÚDE ────────────────────────────────────────────────────
            [
                'slug' => 'saude_clinica_medica',
                'category' => 'saude',
                'name' => 'Clínica Médica',
                'icon' => 'bi-heart-pulse',
                'color' => '#059669',
                'description' => 'Captação e agendamento de consultas para clínicas médicas',
                'stages' => [
                    ['name' => 'Solicitação Recebida', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar especialidade desejada e convênio', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Consulta Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar lembrete da consulta', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Consulta Realizada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisa de satisfação NPS', 'task_type' => 'task', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Em Tratamento', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Compareceu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'saude_odontologia',
                'category' => 'saude',
                'name' => 'Odontologia',
                'icon' => 'bi-emoji-smile',
                'color' => '#06B6D4',
                'description' => 'Funil de captação, avaliação e tratamento odontológico',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar tipo de tratamento desejado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Avaliação Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar avaliação', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Avaliação Realizada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar plano de tratamento', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Orçamento Enviado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up do orçamento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Tratamento Iniciado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'saude_estetica_spa',
                'category' => 'saude',
                'name' => 'Estética / Spa',
                'icon' => 'bi-flower1',
                'color' => '#EC4899',
                'description' => 'Captação para procedimentos estéticos e tratamentos de spa',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender objetivos e preocupações', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Avaliação Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar avaliação 1 dia antes', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Protocolo Apresentado', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Envio de orçamento personalizado', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Sessão Marcada', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar primeira sessão', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'saude_veterinaria',
                'category' => 'saude',
                'name' => 'Veterinária',
                'icon' => 'bi-bug',
                'color' => '#0891B2',
                'description' => 'Atendimento de pets, consultas e tratamentos veterinários',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar dados do pet e tipo de atendimento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Consulta Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Lembrete da consulta', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Atendido', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Acompanhamento pós-consulta', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── EDUCAÇÃO ─────────────────────────────────────────────────
            [
                'slug' => 'educacao_idiomas',
                'category' => 'educacao',
                'name' => 'Escola de Idiomas',
                'icon' => 'bi-translate',
                'color' => '#7C3AED',
                'description' => 'Captação de alunos para escola de idiomas, com nivelamento e matrícula',
                'stages' => [
                    ['name' => 'Interessado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Identificar idioma e objetivo', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Aula Demo Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar aula demonstrativa', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Aula Demo Realizada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar plano de turmas e valores', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Em Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up de matrícula', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Matriculado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistiu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'educacao_cursos_online',
                'category' => 'educacao',
                'name' => 'Cursos Online',
                'icon' => 'bi-laptop',
                'color' => '#8B5CF6',
                'description' => 'Funil de venda para cursos online e infoprodutos',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar conteúdo de aquecimento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Engajado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar oferta do curso', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Carrinho Aberto', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Recuperar carrinho abandonado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Aluno', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Comprou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'educacao_pre_vestibular',
                'category' => 'educacao',
                'name' => 'Pré-vestibular',
                'icon' => 'bi-mortarboard',
                'color' => '#6D28D9',
                'description' => 'Captação de alunos para cursinho preparatório de vestibular e ENEM',
                'stages' => [
                    ['name' => 'Interessado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar dados do aluno e curso desejado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Visita Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar visita à unidade', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Visita Realizada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar plano e descontos', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Negociando', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up com pais/responsáveis', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Matriculado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistiu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'educacao_pos_graduacao',
                'category' => 'educacao',
                'name' => 'Pós-graduação',
                'icon' => 'bi-book',
                'color' => '#5B21B6',
                'description' => 'Captação para cursos de pós-graduação e MBA',
                'stages' => [
                    ['name' => 'Lead Cadastrado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar grade do curso e diferenciais', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reunião Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar reunião de apresentação', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Em Análise', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up sobre decisão', 'task_type' => 'call', 'priority' => 'medium', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Matriculado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistiu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── RESTAURANTE & FOOD ───────────────────────────────────────
            [
                'slug' => 'restaurante_delivery',
                'category' => 'restaurante_food',
                'name' => 'Delivery',
                'icon' => 'bi-bag',
                'color' => '#DC2626',
                'description' => 'Captação e fidelização de clientes de delivery',
                'stages' => [
                    ['name' => 'Pedido Recebido', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar pedido e endereço', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Em Preparo', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Saiu para Entrega', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Entregue', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Solicitar avaliação NPS', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'restaurante_reservas',
                'category' => 'restaurante_food',
                'name' => 'Reservas / Salão',
                'icon' => 'bi-cup-hot',
                'color' => '#B91C1C',
                'description' => 'Gestão de reservas e ocupação do salão',
                'stages' => [
                    ['name' => 'Solicitação de Reserva', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar disponibilidade de mesa', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reserva Confirmada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Lembrete da reserva 2h antes', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Cliente Atendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisa de satisfação', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'No-show', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'restaurante_eventos',
                'category' => 'restaurante_food',
                'name' => 'Eventos / Buffet',
                'icon' => 'bi-balloon',
                'color' => '#991B1B',
                'description' => 'Vendas de eventos privados, buffets e festas',
                'stages' => [
                    ['name' => 'Briefing', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar dados do evento', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Orçamento Enviado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up do orçamento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Visita ao Espaço', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Tour pelo espaço', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Sinal Recebido', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar contrato para assinatura', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Evento Realizado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── E-COMMERCE ───────────────────────────────────────────────
            [
                'slug' => 'ecommerce_recuperacao_carrinho',
                'category' => 'ecommerce',
                'name' => 'Recuperação de Carrinho',
                'icon' => 'bi-cart-x',
                'color' => '#F97316',
                'description' => 'Recuperação de carrinhos abandonados em e-commerce',
                'stages' => [
                    ['name' => 'Carrinho Abandonado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Primeiro contato (1h após abandono)', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Resposta Recebida', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Tirar dúvidas sobre o produto', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Cupom Oferecido', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Acompanhar uso do cupom', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Compra Recuperada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Sem Resposta', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'ecommerce_b2c_geral',
                'category' => 'ecommerce',
                'name' => 'E-commerce B2C',
                'icon' => 'bi-cart',
                'color' => '#EA580C',
                'description' => 'Funil padrão de e-commerce B2C: dúvida → compra → pós-venda',
                'stages' => [
                    ['name' => 'Lead Interessado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Responder dúvidas iniciais', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Produto Apresentado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar fotos e detalhes', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Negociar valor e condições', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Pago', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar entrega e satisfação', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Não Comprou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── SERVIÇOS B2B ─────────────────────────────────────────────
            [
                'slug' => 'b2b_outbound',
                'category' => 'servicos_b2b',
                'name' => 'B2B Outbound',
                'icon' => 'bi-megaphone',
                'color' => '#1F2937',
                'description' => 'Prospecção ativa B2B com qualificação BANT',
                'stages' => [
                    ['name' => 'Prospect', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisar empresa e tomador de decisão', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Cold Call', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar primeira ligação de prospecção', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Reunião Agendada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião de descoberta (BANT)', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Proposta Enviada', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up da proposta', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Negociação', 'color' => '#FB923C', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião de negociação final', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Fechado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'b2b_inbound',
                'category' => 'servicos_b2b',
                'name' => 'B2B Inbound',
                'icon' => 'bi-funnel',
                'color' => '#374151',
                'description' => 'Qualificação e fechamento de leads inbound B2B',
                'stages' => [
                    ['name' => 'MQL', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar lead recebido', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'SQL', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Agendar demo do produto', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Demo Realizada', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar proposta comercial', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up de negociação', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Cliente', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── MARKETING & AGÊNCIA ──────────────────────────────────────
            [
                'slug' => 'agencia_novos_clientes',
                'category' => 'marketing_agencia',
                'name' => 'Agência — Novos Clientes',
                'icon' => 'bi-bullseye',
                'color' => '#9333EA',
                'description' => 'Captação de novos clientes para agências de marketing',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar tamanho e segmento', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Diagnóstico', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião de diagnóstico digital', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Proposta', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar proposta personalizada', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up e ajustes', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'agencia_upsell',
                'category' => 'marketing_agencia',
                'name' => 'Agência — Upsell',
                'icon' => 'bi-graph-up-arrow',
                'color' => '#7E22CE',
                'description' => 'Upsell e expansão de serviços para clientes ativos da agência',
                'stages' => [
                    ['name' => 'Oportunidade', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Identificar gap no atendimento atual', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Conversa Iniciada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Agendar reunião de revisão', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Proposta Adicional', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar novo escopo', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Aprovado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Recusado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── BELEZA & ESTÉTICA ────────────────────────────────────────
            [
                'slug' => 'beleza_salao',
                'category' => 'beleza_estetica',
                'name' => 'Salão de Cabelo',
                'icon' => 'bi-scissors',
                'color' => '#F472B6',
                'description' => 'Captação e fidelização de clientes para salão de beleza',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Identificar serviço desejado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Agendado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar horário 1 dia antes', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Atendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisa de satisfação', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Não Compareceu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'beleza_barbearia',
                'category' => 'beleza_estetica',
                'name' => 'Barbearia',
                'icon' => 'bi-person-bounding-box',
                'color' => '#92400E',
                'description' => 'Agendamentos e fidelização em barbearia',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Identificar serviço desejado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Agendado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Lembrete do horário', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Atendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Convidar para programa de fidelidade', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Não Compareceu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'beleza_estetica_avancada',
                'category' => 'beleza_estetica',
                'name' => 'Estética Avançada',
                'icon' => 'bi-stars',
                'color' => '#DB2777',
                'description' => 'Procedimentos estéticos avançados (botox, preenchimento, peeling)',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender objetivos do cliente', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Avaliação Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar avaliação', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Plano Apresentado', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar orçamento detalhado', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Procedimento Marcado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Instruções pré-procedimento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Cliente', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── AUTOMOTIVO ───────────────────────────────────────────────
            [
                'slug' => 'automotivo_concessionaria',
                'category' => 'automotivo',
                'name' => 'Concessionária',
                'icon' => 'bi-car-front',
                'color' => '#1E3A8A',
                'description' => 'Venda de veículos novos em concessionária',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar interesse no modelo', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Test Drive Agendado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar test drive', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Test Drive Realizado', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar proposta comercial', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Em Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Simulação de financiamento', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Vendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'automotivo_oficina',
                'category' => 'automotivo',
                'name' => 'Oficina Mecânica',
                'icon' => 'bi-wrench',
                'color' => '#1E40AF',
                'description' => 'Atendimento e manutenção de veículos em oficina',
                'stages' => [
                    ['name' => 'Solicitação', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar dados do veículo e problema', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Diagnóstico', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar inspeção do veículo', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Orçamento Aprovado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Iniciar reparo', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Concluído', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisa de satisfação', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Não Aprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── ADVOCACIA ────────────────────────────────────────────────
            [
                'slug' => 'advocacia_consulta',
                'category' => 'advocacia',
                'name' => 'Consulta Inicial',
                'icon' => 'bi-briefcase',
                'color' => '#0F172A',
                'description' => 'Captação e qualificação de consultas jurídicas',
                'stages' => [
                    ['name' => 'Contato Recebido', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Triagem inicial do caso', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Consulta Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar reunião com advogado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Análise do Caso', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Estudar documentos enviados', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Proposta de Honorários', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar contrato', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Cliente', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Contratou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'advocacia_trabalhista',
                'category' => 'advocacia',
                'name' => 'Casos Trabalhistas',
                'icon' => 'bi-file-earmark-text',
                'color' => '#1E293B',
                'description' => 'Captação e gestão de processos trabalhistas',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar histórico do caso', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Análise de Viabilidade', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Calcular valores devidos', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Contrato Enviado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up de assinatura', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Processo Aberto', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistência', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── TECNOLOGIA & SAAS ────────────────────────────────────────
            [
                'slug' => 'saas_trial',
                'category' => 'tecnologia_saas',
                'name' => 'SaaS — Trial → Pago',
                'icon' => 'bi-cloud-check',
                'color' => '#0EA5E9',
                'description' => 'Conversão de trial gratuito em assinatura paga',
                'stages' => [
                    ['name' => 'Trial Iniciado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Onboarding inicial', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Engajado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Sessão de sucesso do cliente', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Trial Expirando', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar planos pagos', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Cliente Pagante', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Churn', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'saas_enterprise',
                'category' => 'tecnologia_saas',
                'name' => 'SaaS Enterprise',
                'icon' => 'bi-building',
                'color' => '#0369A1',
                'description' => 'Vendas enterprise SaaS com ciclo longo e múltiplos stakeholders',
                'stages' => [
                    ['name' => 'Lead Qualificado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Discovery call com o tomador de decisão', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Demo Personalizada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar demo customizada', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'POC / Trial', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Acompanhar uso durante POC', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Proposta', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar proposta comercial', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Negociação Jurídica', 'color' => '#FB923C', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Revisar contrato com jurídico', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Cliente', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── COACH & CONSULTORIA ──────────────────────────────────────
            [
                'slug' => 'coach_captacao',
                'category' => 'coach_consultoria',
                'name' => 'Coach — Captação',
                'icon' => 'bi-person-workspace',
                'color' => '#6366F1',
                'description' => 'Captação de novos clientes para mentoria/coach',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Sessão diagnóstico gratuita', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Sessão Strategy Call', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar sessão estratégica', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Proposta Apresentada', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up da proposta', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Mentorando', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'consultoria_diagnostico',
                'category' => 'coach_consultoria',
                'name' => 'Consultoria Empresarial',
                'icon' => 'bi-clipboard-data',
                'color' => '#4F46E5',
                'description' => 'Diagnóstico e venda de projetos de consultoria empresarial',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião exploratória', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Diagnóstico Realizado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Elaborar relatório de diagnóstico', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Proposta', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar projeto', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Ajustar escopo e valores', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Projeto Iniciado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── EVENTOS ──────────────────────────────────────────────────
            [
                'slug' => 'eventos_casamentos',
                'category' => 'eventos',
                'name' => 'Casamentos',
                'icon' => 'bi-heart',
                'color' => '#E11D48',
                'description' => 'Captação e venda de pacotes para casamentos',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar data e estilo do evento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reunião Inicial', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar portfólio e pacotes', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Visita ao Espaço', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Tour pelo espaço escolhido', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Proposta', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar orçamento detalhado', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Reservado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'eventos_corporativos',
                'category' => 'eventos',
                'name' => 'Eventos Corporativos',
                'icon' => 'bi-building-fill',
                'color' => '#BE185D',
                'description' => 'Vendas de eventos corporativos, convenções e workshops',
                'stages' => [
                    ['name' => 'Briefing Recebido', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender objetivo e número de pessoas', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Proposta Técnica', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar proposta inicial', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Visita Técnica', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Visita ao espaço com cliente', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Contrato', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Negociar e assinar contrato', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Realizado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'eventos_infantis',
                'category' => 'eventos',
                'name' => 'Aniversários Infantis',
                'icon' => 'bi-balloon-heart',
                'color' => '#FB7185',
                'description' => 'Festas infantis, decoração e organização',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar tema e idade da criança', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Orçamento', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar orçamento personalizado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Sinal Pago', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar fornecedores e cronograma', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Festa Realizada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── CONSTRUÇÃO & REFORMA ─────────────────────────────────────
            [
                'slug' => 'construcao_obra_residencial',
                'category' => 'construcao_reforma',
                'name' => 'Obra Residencial',
                'icon' => 'bi-house-gear',
                'color' => '#A16207',
                'description' => 'Construção de casas e residências do zero',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender escopo e localização', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Visita ao Terreno', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Visita técnica ao terreno', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Projeto', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Elaborar projeto arquitetônico', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 14],
                    ]],
                    ['name' => 'Orçamento', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar orçamento detalhado', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Contrato', 'color' => '#FB923C', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Assinar contrato e cronograma', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Obra Iniciada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'construcao_reforma',
                'category' => 'construcao_reforma',
                'name' => 'Reforma de Apartamento',
                'icon' => 'bi-hammer',
                'color' => '#CA8A04',
                'description' => 'Reformas residenciais e comerciais',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar escopo da reforma', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Visita Técnica', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Medição no local', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Orçamento Enviado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up de aprovação', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Contratado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'construcao_materiais',
                'category' => 'construcao_reforma',
                'name' => 'Loja de Materiais',
                'icon' => 'bi-box-seam',
                'color' => '#854D0E',
                'description' => 'Vendas em loja de materiais de construção',
                'stages' => [
                    ['name' => 'Cotação Solicitada', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Verificar estoque dos itens', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Orçamento Enviado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar cotação detalhada', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up e ajustes de preço', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Pago', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── TURISMO ──────────────────────────────────────────────────
            [
                'slug' => 'turismo_pacotes',
                'category' => 'turismo',
                'name' => 'Pacotes de Viagem',
                'icon' => 'bi-airplane',
                'color' => '#0EA5E9',
                'description' => 'Vendas de pacotes turísticos por agência',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender destino, datas e perfil', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Cotação Enviada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar opções de pacotes', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up e ajustes', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Reservado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'turismo_hospedagem',
                'category' => 'turismo',
                'name' => 'Hospedagem',
                'icon' => 'bi-house-add',
                'color' => '#0284C7',
                'description' => 'Reservas de pousadas, hotéis e resorts',
                'stages' => [
                    ['name' => 'Solicitação', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Verificar disponibilidade nas datas', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Tarifa Enviada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar valores e condições', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reserva Confirmada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Lembrete pré check-in', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 7],
                    ]],
                    ['name' => 'Cancelado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── FITNESS ──────────────────────────────────────────────────
            [
                'slug' => 'fitness_academia',
                'category' => 'fitness',
                'name' => 'Academia',
                'icon' => 'bi-bicycle',
                'color' => '#16A34A',
                'description' => 'Captação de novos alunos para academia',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Convidar para aula experimental', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Aula Experimental', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar aula experimental', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Avaliação Física', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar planos e descontos', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Matriculado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Desistiu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'fitness_personal',
                'category' => 'fitness',
                'name' => 'Personal Trainer',
                'icon' => 'bi-person-arms-up',
                'color' => '#15803D',
                'description' => 'Captação de alunos para personal trainer',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender objetivos e disponibilidade', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Avaliação Inicial', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar avaliação física', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Plano Apresentado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up do plano', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Aluno Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── FINANCEIRO ───────────────────────────────────────────────
            [
                'slug' => 'financeiro_consignado',
                'category' => 'financeiro',
                'name' => 'Crédito Consignado',
                'icon' => 'bi-cash-coin',
                'color' => '#047857',
                'description' => 'Captação e contratação de crédito consignado',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar tipo de benefício e valor', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Simulação', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar simulação', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Documentos', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar documentos', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Contratado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Aprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'financeiro_investimentos',
                'category' => 'financeiro',
                'name' => 'Investimentos',
                'icon' => 'bi-graph-up',
                'color' => '#065F46',
                'description' => 'Captação de investidores para assessoria financeira',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar perfil e patrimônio', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reunião Inicial', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião de apresentação', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Suitability', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Aplicar análise de perfil', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Carteira Sugerida', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar carteira recomendada', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'financeiro_emprestimo',
                'category' => 'financeiro',
                'name' => 'Empréstimo Pessoal',
                'icon' => 'bi-currency-dollar',
                'color' => '#059669',
                'description' => 'Captação e contratação de empréstimo pessoal',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar valor desejado e renda', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Análise de Crédito', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Submeter para análise', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Aprovado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar condições', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Contratado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Reprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── RECURSOS HUMANOS ─────────────────────────────────────────
            [
                'slug' => 'rh_recrutamento',
                'category' => 'recursos_humanos',
                'name' => 'Recrutamento',
                'icon' => 'bi-people',
                'color' => '#7E22CE',
                'description' => 'Pipeline de recrutamento e seleção de candidatos',
                'stages' => [
                    ['name' => 'Candidatos', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Triagem de currículo', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Entrevista RH', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Realizar entrevista inicial', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Entrevista Técnica', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Avaliação técnica com gestor', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Proposta', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar proposta salarial', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Contratado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Aprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── PET ──────────────────────────────────────────────────────
            [
                'slug' => 'pet_banho_tosa',
                'category' => 'pet',
                'name' => 'Banho e Tosa',
                'icon' => 'bi-droplet',
                'color' => '#22D3EE',
                'description' => 'Agendamentos e fidelização de pet shop',
                'stages' => [
                    ['name' => 'Solicitação', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Coletar dados do pet e serviço', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Agendado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Confirmar horário', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Atendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Pesquisa de satisfação', 'task_type' => 'whatsapp', 'priority' => 'low', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Não Compareceu', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'pet_adestramento',
                'category' => 'pet',
                'name' => 'Adestramento',
                'icon' => 'bi-bullseye',
                'color' => '#0E7490',
                'description' => 'Captação para serviços de adestramento canino',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender comportamentos do pet', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Visita Agendada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Avaliação na casa do cliente', 'task_type' => 'visit', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Plano Apresentado', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up do plano', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Fechou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── RELIGIOSO ────────────────────────────────────────────────
            [
                'slug' => 'religioso_doacoes',
                'category' => 'religioso',
                'name' => 'Doações Recorrentes',
                'icon' => 'bi-gift',
                'color' => '#9333EA',
                'description' => 'Captação de doadores recorrentes para igreja ou ONG',
                'stages' => [
                    ['name' => 'Interessado', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar projetos da instituição', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Conversa Iniciada', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Compartilhar testemunhos', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Doação Confirmada', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar agradecimento personalizado', 'task_type' => 'whatsapp', 'priority' => 'medium', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Não Doou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── VENDAS B2C ───────────────────────────────────────────────
            [
                'slug' => 'b2c_whatsapp_commerce',
                'category' => 'vendas_b2c',
                'name' => 'WhatsApp Commerce',
                'icon' => 'bi-whatsapp',
                'color' => '#25D366',
                'description' => 'Vendas diretas via WhatsApp',
                'stages' => [
                    ['name' => 'Mensagem Recebida', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Responder rapidamente', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Produto Apresentado', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar fotos e vídeos', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Negociar valor e forma de pagamento', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Pago', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Comprou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'b2c_loja_fisica',
                'category' => 'vendas_b2c',
                'name' => 'Loja Física',
                'icon' => 'bi-shop-window',
                'color' => '#16A34A',
                'description' => 'Atendimento e venda em showroom físico',
                'stages' => [
                    ['name' => 'Visitante', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Abordar e qualificar interesse', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Demonstração', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar produtos', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Em Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Follow-up se não fechou na hora', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Vendido', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Não Comprou', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── TECNOLOGIA — outros ──────────────────────────────────────
            [
                'slug' => 'tech_manutencao_pc',
                'category' => 'tecnologia_saas',
                'name' => 'Manutenção PC/Notebook',
                'icon' => 'bi-pc-display',
                'color' => '#475569',
                'description' => 'Atendimento de assistência técnica de informática',
                'stages' => [
                    ['name' => 'Solicitação', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Diagnosticar problema relatado', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Equipamento Recebido', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Análise técnica detalhada', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 1],
                    ]],
                    ['name' => 'Orçamento', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar orçamento ao cliente', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Reparado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Avisar cliente sobre retirada', 'task_type' => 'whatsapp', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Não Aprovado', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'tech_dev_sob_demanda',
                'category' => 'tecnologia_saas',
                'name' => 'Dev sob Demanda',
                'icon' => 'bi-code-slash',
                'color' => '#0369A1',
                'description' => 'Vendas de projetos de desenvolvimento de software',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Briefing inicial', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Discovery', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Reunião de levantamento técnico', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Proposta Técnica', 'color' => '#A78BFA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Elaborar escopo e orçamento', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Negociação', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Ajustar escopo e prazos', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 5],
                    ]],
                    ['name' => 'Projeto Iniciado', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],

            // ── MARKETING DIGITAL ────────────────────────────────────────
            [
                'slug' => 'marketing_trafego_pago',
                'category' => 'marketing_agencia',
                'name' => 'Tráfego Pago',
                'icon' => 'bi-cursor',
                'color' => '#A21CAF',
                'description' => 'Vendas de gestão de tráfego pago Meta/Google',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Qualificar verba e mercado', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Análise de Conta', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Auditar contas atuais', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Proposta', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Apresentar plano de gestão', 'task_type' => 'meeting', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
            [
                'slug' => 'marketing_social_media',
                'category' => 'marketing_agencia',
                'name' => 'Social Media',
                'icon' => 'bi-chat-square-quote',
                'color' => '#C026D3',
                'description' => 'Vendas de gestão de redes sociais mensal',
                'stages' => [
                    ['name' => 'Lead Novo', 'color' => '#9CA3AF', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Entender objetivo da marca', 'task_type' => 'call', 'priority' => 'high', 'due_date_offset' => 0],
                    ]],
                    ['name' => 'Diagnóstico', 'color' => '#60A5FA', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Análise das redes atuais', 'task_type' => 'task', 'priority' => 'high', 'due_date_offset' => 3],
                    ]],
                    ['name' => 'Proposta', 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false, 'required_tasks' => [
                        ['subject' => 'Enviar plano mensal', 'task_type' => 'email', 'priority' => 'high', 'due_date_offset' => 2],
                    ]],
                    ['name' => 'Cliente Ativo', 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'required_tasks' => []],
                    ['name' => 'Perdido', 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'required_tasks' => []],
                ],
            ],
        ];
    }

    /**
     * Aplica overrides de tradução de categorias quando o locale ativo
     * tem um arquivo lang/<locale>/pipeline_templates.php.
     *
     * @param  array<string,string>  $defaults
     * @return array<string,string>
     */
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
     * Aplica overrides de tradução de templates (nome, descrição, stages e
     * tarefas obrigatórias) quando o locale ativo tem traduções.
     *
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

            if (isset($tr['stages']) && is_array($tr['stages']) && isset($template['stages']) && is_array($template['stages'])) {
                foreach ($template['stages'] as $stageIdx => &$stage) {
                    if (! isset($tr['stages'][$stageIdx]) || ! is_array($tr['stages'][$stageIdx])) {
                        continue;
                    }
                    $stageTr = $tr['stages'][$stageIdx];

                    if (isset($stageTr['name']) && is_string($stageTr['name']) && $stageTr['name'] !== '') {
                        $stage['name'] = $stageTr['name'];
                    }

                    if (isset($stageTr['tasks']) && is_array($stageTr['tasks'])
                        && isset($stage['required_tasks']) && is_array($stage['required_tasks'])) {
                        foreach ($stage['required_tasks'] as $taskIdx => &$task) {
                            if (isset($stageTr['tasks'][$taskIdx]) && is_string($stageTr['tasks'][$taskIdx]) && $stageTr['tasks'][$taskIdx] !== '') {
                                $task['subject'] = $stageTr['tasks'][$taskIdx];
                            }
                        }
                        unset($task);
                    }
                }
                unset($stage);
            }
        }
        unset($template);

        return $templates;
    }

    /**
     * Carrega o array de traduções do arquivo lang/<locale>/pipeline_templates.php.
     * Cacheia em memória por request. Retorna [] se locale é pt_BR ou se o
     * arquivo não existe (defaults inline já são em PT).
     *
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

        $path = lang_path("{$locale}/pipeline_templates.php");
        if (! is_file($path)) {
            return $cache[$locale] = [];
        }

        $data = require $path;
        return $cache[$locale] = is_array($data) ? $data : [];
    }
}
