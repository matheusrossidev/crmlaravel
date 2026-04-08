<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Biblioteca de templates de Nurture Sequences prontas para diferentes nichos.
 *
 * Mesmo padrão do PipelineTemplates: hardcoded em PHP, defaults pt_BR inline,
 * i18n via lang/<locale>/sequence_templates.php.
 *
 * Cada template tem:
 *  - slug, category, name, description, icon
 *  - sequence: array com nome, descrição, channel, exit_on_reply, etc
 *  - steps[]: array de steps com position, delay_minutes, type, config
 *
 * Step types suportados:
 *  - 'message': envia texto (config: {body, media_type='text'})
 *  - 'action': executa ação (config: {type, params})
 *  - 'wait_reply': pausa esperando resposta (config: {timeout_minutes})
 *  - 'condition': desvio condicional (config: {field, operator, value, step_if_false})
 *
 * Variáveis interpoláveis em body: {{name}}, {{phone}}, {{email}}, {{company}}
 *
 * Foco: 9 nichos PME (mesmos do ScoringRuleTemplates / AutomationTemplates).
 */
final class SequenceTemplates
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
                'slug'        => 'imo_boas_vindas',
                'category'    => 'imobiliaria',
                'name'        => 'Boas-vindas Imobiliária',
                'description' => 'Sequência de 3 mensagens em 5 dias para apresentar a empresa e capturar interesse.',
                'icon'        => 'bi-house-door',
                'sequence' => [
                    'name'                  => 'Boas-vindas Imobiliária',
                    'description'           => 'Apresentação inicial em 3 mensagens',
                    'channel'               => 'whatsapp',
                    'exit_on_reply'         => true,
                    'exit_on_stage_change'  => false,
                ],
                'steps' => [
                    [
                        'position'      => 1,
                        'delay_minutes' => 0,
                        'type'          => 'message',
                        'config'        => [
                            'body'       => 'Olá {{name}}! Sou da [Sua Empresa] e ficamos felizes que você está procurando seu próximo imóvel. 🏠',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 2,
                        'delay_minutes' => 1440, // 1 dia
                        'type'          => 'message',
                        'config'        => [
                            'body'       => '{{name}}, separamos algumas opções que combinam com o que você procura. Quer ver o portfólio completo?',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 3,
                        'delay_minutes' => 2880, // +2 dias
                        'type'          => 'message',
                        'config'        => [
                            'body'       => '{{name}}, podemos agendar uma visita esta semana? Nosso corretor está à disposição. 📞',
                            'media_type' => 'text',
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_reativacao_7d',
                'category'    => 'imobiliaria',
                'name'        => 'Reativação 7 dias sem resposta',
                'description' => 'Sequência de 3 mensagens para tentar reativar leads que não responderam há 7 dias.',
                'icon'        => 'bi-arrow-clockwise',
                'sequence' => [
                    'name'                  => 'Reativação 7d',
                    'description'           => 'Tentativa de reativar leads frios',
                    'channel'               => 'whatsapp',
                    'exit_on_reply'         => true,
                    'exit_on_stage_change'  => true,
                ],
                'steps' => [
                    [
                        'position'      => 1,
                        'delay_minutes' => 0,
                        'type'          => 'message',
                        'config'        => [
                            'body'       => '{{name}}, sentimos sua falta! Você ainda está procurando um imóvel? 🏡',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 2,
                        'delay_minutes' => 2880, // 2 dias
                        'type'          => 'message',
                        'config'        => [
                            'body'       => 'Temos novidades no portfólio que combinam com seu perfil, {{name}}. Quer ver?',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 3,
                        'delay_minutes' => 4320, // +3 dias
                        'type'          => 'message',
                        'config'        => [
                            'body'       => 'Última tentativa, {{name}}! Se quiser conversar, é só responder essa mensagem. Estamos à disposição.',
                            'media_type' => 'text',
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'imo_pos_visita',
                'category'    => 'imobiliaria',
                'name'        => 'Pós-visita',
                'description' => 'Acompanhamento de 4 mensagens em 14 dias após visita ao imóvel.',
                'icon'        => 'bi-clipboard-check',
                'sequence' => [
                    'name'                  => 'Pós-visita',
                    'description'           => 'Follow-up depois da visita ao imóvel',
                    'channel'               => 'whatsapp',
                    'exit_on_reply'         => true,
                    'exit_on_stage_change'  => true,
                ],
                'steps' => [
                    [
                        'position'      => 1,
                        'delay_minutes' => 60, // 1h depois
                        'type'          => 'message',
                        'config'        => [
                            'body'       => 'Oi {{name}}! Obrigado pela visita hoje. O que achou do imóvel? 🏠',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 2,
                        'delay_minutes' => 1440, // 1 dia
                        'type'          => 'message',
                        'config'        => [
                            'body'       => '{{name}}, alguma dúvida sobre o imóvel ou processo de fechamento? Estou aqui pra ajudar.',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 3,
                        'delay_minutes' => 4320, // +3 dias
                        'type'          => 'message',
                        'config'        => [
                            'body'       => '{{name}}, separamos outras 3 opções parecidas com o que você visitou. Quer dar uma olhada?',
                            'media_type' => 'text',
                        ],
                    ],
                    [
                        'position'      => 4,
                        'delay_minutes' => 14400, // +10 dias
                        'type'          => 'message',
                        'config'        => [
                            'body'       => 'Última oferta da semana, {{name}}! Quer revisar suas opções juntos?',
                            'media_type' => 'text',
                        ],
                    ],
                ],
            ],

            // ── SAÚDE ────────────────────────────────────────────────────
            [
                'slug' => 'sau_pre_consulta', 'category' => 'saude',
                'name' => 'Pré-consulta',
                'description' => '3 mensagens preparando o paciente para a consulta.',
                'icon' => 'bi-clipboard-pulse',
                'sequence' => ['name' => 'Pré-consulta', 'description' => 'Lembrete e preparação', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! Sua consulta está confirmada. 🏥 Em breve enviamos as orientações.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, lembre-se de chegar 15 minutos antes do horário.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Lembrete: amanhã é sua consulta às [horário]. Estamos te esperando!', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'sau_pos_consulta', 'category' => 'saude',
                'name' => 'Pós-consulta',
                'description' => '4 mensagens após a consulta com orientações e fidelização.',
                'icon' => 'bi-heart-pulse',
                'sequence' => ['name' => 'Pós-consulta', 'description' => 'Cuidados e fidelização', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 60, 'type' => 'message', 'config' => ['body' => '{{name}}, foi um prazer atender você hoje! 💙 Qualquer dúvida sobre o tratamento, é só chamar.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, como você está? Tudo bem com a recomendação que passamos?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, em breve será hora do seu retorno. Quer já agendar?', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 43200, 'type' => 'message', 'config' => ['body' => '{{name}}, faz um mês desde sua última consulta. Vamos agendar uma avaliação?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'sau_reativacao_90d', 'category' => 'saude',
                'name' => 'Reativação 90 dias',
                'description' => '3 mensagens para pacientes que não retornaram em 3 meses.',
                'icon' => 'bi-arrow-clockwise',
                'sequence' => ['name' => 'Reativação 90d', 'description' => 'Reativar pacientes inativos', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, faz 3 meses que não nos vemos. Como está sua saúde? 💙', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, é importante manter o acompanhamento. Quer agendar uma consulta?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => 'Última lembrança, {{name}}. Estamos aqui quando precisar.', 'media_type' => 'text']],
                ],
            ],

            // ── E-COMMERCE ───────────────────────────────────────────────
            [
                'slug' => 'eco_carrinho_recovery', 'category' => 'ecommerce',
                'name' => 'Recuperação de carrinho',
                'description' => '3 mensagens em 72h para tentar recuperar carrinho abandonado.',
                'icon' => 'bi-cart-x',
                'sequence' => ['name' => 'Recuperação carrinho', 'description' => 'Reverter abandono', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 60, 'type' => 'message', 'config' => ['body' => 'Oi {{name}}! 🛒 Você esqueceu alguns produtos no carrinho. Quer finalizar a compra?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, separamos um cupom de 5% OFF pra você finalizar agora!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Última chance, {{name}}! Seus produtos podem acabar. ⏰', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'eco_pos_compra', 'category' => 'ecommerce',
                'name' => 'Pós-compra (5 mensagens)',
                'description' => '5 mensagens em 30 dias para fidelizar e gerar cross-sell.',
                'icon' => 'bi-bag-check',
                'sequence' => ['name' => 'Pós-compra', 'description' => 'Fidelização e cross-sell', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 60, 'type' => 'message', 'config' => ['body' => 'Obrigado pela compra, {{name}}! 🎉 Seu pedido está sendo preparado.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, seu pedido já chegou? Conta pra gente como foi a experiência! ⭐', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, separamos produtos que combinam com sua última compra. Quer ver?', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 28800, 'type' => 'message', 'config' => ['body' => 'Oi {{name}}! 💝 Tem cupom especial pra você. Vamos usar?', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 43200, 'type' => 'message', 'config' => ['body' => '{{name}}, faz 30 dias desde sua compra. Que tal renovar?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'eco_winback', 'category' => 'ecommerce',
                'name' => 'Win-back inativos',
                'description' => '4 mensagens para recuperar clientes que não compram há tempos.',
                'icon' => 'bi-arrow-counterclockwise',
                'sequence' => ['name' => 'Win-back', 'description' => 'Reativar clientes antigos', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, sentimos sua falta! 💔 Como você está?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Temos novidades incríveis pra você ver, {{name}}!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 7200, 'type' => 'message', 'config' => ['body' => 'Cupom especial pra te trazer de volta: VOLTOU20 (20% OFF), {{name}}!', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => 'Última oferta exclusiva, {{name}}. Não perca!', 'media_type' => 'text']],
                ],
            ],

            // ── EDUCAÇÃO ─────────────────────────────────────────────────
            [
                'slug' => 'edu_nutricao', 'category' => 'educacao',
                'name' => 'Nutrição educacional (7 dias)',
                'description' => '7 mensagens em 14 dias com conteúdo gradual de valor.',
                'icon' => 'bi-book',
                'sequence' => ['name' => 'Nutrição educacional', 'description' => 'Conteúdo gradual', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! 📚 Aqui está sua primeira lição gratuita.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => '{{name}}, lição 2: dica prática que você pode aplicar hoje.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 5760, 'type' => 'message', 'config' => ['body' => 'Lição 3, {{name}}: case de sucesso real.', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 8640, 'type' => 'message', 'config' => ['body' => '{{name}}, hoje uma reflexão importante sobre seu objetivo.', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 11520, 'type' => 'message', 'config' => ['body' => 'Lição 5: erros mais comuns que você precisa evitar, {{name}}.', 'media_type' => 'text']],
                    ['position' => 6, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, está gostando das lições? Temos um curso completo pra você!', 'media_type' => 'text']],
                    ['position' => 7, 'delay_minutes' => 17280, 'type' => 'message', 'config' => ['body' => 'Última lição grátis, {{name}}. Pronto pra dar o próximo passo?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'edu_pre_matricula', 'category' => 'educacao',
                'name' => 'Pré-matrícula',
                'description' => '5 mensagens para fechar matrícula.',
                'icon' => 'bi-mortarboard',
                'sequence' => ['name' => 'Pré-matrícula', 'description' => 'Conversão de interessados', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! Vi que você se interessou pelo nosso curso. 🎓', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, separei um vídeo da nossa metodologia. Vai gostar!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Olha esse depoimento de aluno, {{name}}: [LINK]', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, temos uma condição especial pra essa semana!', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 5760, 'type' => 'message', 'config' => ['body' => 'Última chamada, {{name}}! Vagas se esgotando.', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'edu_onboarding_aluno', 'category' => 'educacao',
                'name' => 'Onboarding do aluno',
                'description' => '4 mensagens para integrar novo aluno à plataforma.',
                'icon' => 'bi-person-plus',
                'sequence' => ['name' => 'Onboarding aluno', 'description' => 'Integração inicial', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Bem-vindo(a), {{name}}! 🎉 Sua jornada começa agora.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, conseguiu acessar a plataforma? Aqui vai o tutorial.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, como estão os primeiros estudos? Qualquer dúvida me chama.', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => 'Uma semana já, {{name}}! Está conseguindo seguir o ritmo?', 'media_type' => 'text']],
                ],
            ],

            // ── BELEZA & ESTÉTICA ────────────────────────────────────────
            [
                'slug' => 'bel_boas_vindas', 'category' => 'beleza',
                'name' => 'Boas-vindas + tabela de preços',
                'description' => '3 mensagens para apresentar serviços e converter.',
                'icon' => 'bi-flower1',
                'sequence' => ['name' => 'Boas-vindas Beleza', 'description' => 'Apresentação inicial', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! 💄 Que bom te receber. Aqui está a tabela de serviços.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, alguma dúvida sobre os serviços? Estou aqui pra ajudar.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => '{{name}}, quer agendar pra essa semana? Tenho horários disponíveis!', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'bel_lembrete_retorno', 'category' => 'beleza',
                'name' => 'Lembrete de retorno (30 dias)',
                'description' => '4 mensagens para trazer cliente de volta após 30 dias.',
                'icon' => 'bi-calendar-event',
                'sequence' => ['name' => 'Lembrete retorno 30d', 'description' => 'Reativação cliente', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, faz 30 dias desde nosso último encontro. 💆‍♀️ Quer agendar?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => 'Oi {{name}}! Que tal um agrado essa semana? Tenho horários!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 7200, 'type' => 'message', 'config' => ['body' => '{{name}}, especial pra você: 10% OFF no próximo agendamento!', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => 'Última chance, {{name}}! Quero te ver de novo. ❤️', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'bel_aniversario', 'category' => 'beleza',
                'name' => 'Aniversário com presente',
                'description' => '1 mensagem especial no aniversário com presente.',
                'icon' => 'bi-gift',
                'sequence' => ['name' => 'Aniversário Beleza', 'description' => 'Mensagem de aniversário', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Parabéns, {{name}}! 🎂✨ Como presente, 25% OFF no serviço da sua escolha esse mês.', 'media_type' => 'text']],
                ],
            ],

            // ── B2B SERVIÇOS ─────────────────────────────────────────────
            [
                'slug' => 'b2b_nurture_longo', 'category' => 'b2b_servicos',
                'name' => 'Nurture longo B2B (8 mensagens)',
                'description' => '8 mensagens em 30 dias com conteúdo de valor para B2B.',
                'icon' => 'bi-briefcase',
                'sequence' => ['name' => 'Nurture B2B longo', 'description' => 'Conteúdo de valor B2B', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! 👔 Obrigado pelo interesse. Aqui está nosso material de apresentação.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, separei um case de sucesso de empresa parecida com a sua.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 8640, 'type' => 'message', 'config' => ['body' => 'Olha esse artigo, {{name}}: 5 erros comuns no seu setor.', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, conseguiu olhar nosso material? Posso esclarecer dúvidas.', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 20160, 'type' => 'message', 'config' => ['body' => 'Webinar gratuito essa semana, {{name}}. Quer participar?', 'media_type' => 'text']],
                    ['position' => 6, 'delay_minutes' => 28800, 'type' => 'message', 'config' => ['body' => '{{name}}, ROI médio dos nossos clientes B2B: 3x em 6 meses.', 'media_type' => 'text']],
                    ['position' => 7, 'delay_minutes' => 36000, 'type' => 'message', 'config' => ['body' => '{{name}}, vamos marcar uma demo personalizada essa semana?', 'media_type' => 'text']],
                    ['position' => 8, 'delay_minutes' => 43200, 'type' => 'message', 'config' => ['body' => 'Última oportunidade, {{name}}. Estou à disposição.', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'b2b_pos_demo', 'category' => 'b2b_servicos',
                'name' => 'Pós-demo B2B',
                'description' => '5 mensagens em 14 dias após uma demo.',
                'icon' => 'bi-camera-video',
                'sequence' => ['name' => 'Pós-demo B2B', 'description' => 'Conversão pós-demo', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 60, 'type' => 'message', 'config' => ['body' => '{{name}}, obrigado pela demo hoje! Aqui está o material que prometi.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, alguma dúvida que ficou da apresentação?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => 'Olha como nossos clientes implementaram, {{name}}: [case]', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => '{{name}}, posso enviar uma proposta comercial?', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 20160, 'type' => 'message', 'config' => ['body' => '{{name}}, ainda fazem sentido nossas soluções? Quer reagendar?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'b2b_reativacao_fria', 'category' => 'b2b_servicos',
                'name' => 'Reativação fria B2B',
                'description' => '4 mensagens para tentar reativar leads frios.',
                'icon' => 'bi-thermometer-snow',
                'sequence' => ['name' => 'Reativação fria B2B', 'description' => 'Reaquecer leads', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, faz um tempo que não conversamos. Tudo bem por aí?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, lançamos algumas novidades. Quer ver?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => '{{name}}, condições especiais pra empresas como a sua esse mês.', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 20160, 'type' => 'message', 'config' => ['body' => 'Última oportunidade do mês, {{name}}. Vamos conversar?', 'media_type' => 'text']],
                ],
            ],

            // ── MARKETING & AGÊNCIA ──────────────────────────────────────
            [
                'slug' => 'agm_cases', 'category' => 'agencia_marketing',
                'name' => 'Cases de sucesso',
                'description' => '5 mensagens com cases para nutrir leads em consideração.',
                'icon' => 'bi-trophy',
                'sequence' => ['name' => 'Cases de sucesso', 'description' => 'Nutrição com prova social', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, separei alguns cases que vão te interessar. 📈', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Case 1: empresa do seu setor que cresceu 200% em 6 meses, {{name}}.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 5760, 'type' => 'message', 'config' => ['body' => 'Case 2: como uma PME triplicou os leads em 90 dias, {{name}}.', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 8640, 'type' => 'message', 'config' => ['body' => 'Case 3: agência saiu de R$5k pra R$50k/mês com nossa metodologia.', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 11520, 'type' => 'message', 'config' => ['body' => '{{name}}, gostaria de ver como aplicar isso na sua empresa?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'agm_pos_cotacao', 'category' => 'agencia_marketing',
                'name' => 'Pós-cotação',
                'description' => '4 mensagens para fechar venda após enviar cotação.',
                'icon' => 'bi-file-earmark-text',
                'sequence' => ['name' => 'Pós-cotação', 'description' => 'Fechamento pós-proposta', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, conseguiu olhar nossa proposta? Posso esclarecer dúvidas.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, ficou claro o ROI esperado? Posso detalhar mais.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 7200, 'type' => 'message', 'config' => ['body' => '{{name}}, vamos marcar uma call pra alinhar próximos passos?', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, ainda estamos com a vaga aberta pra esse mês. Decisão?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'agm_reativacao_frios', 'category' => 'agencia_marketing',
                'name' => 'Reativação leads frios',
                'description' => '3 mensagens para reativar leads que não responderam.',
                'icon' => 'bi-arrow-clockwise',
                'sequence' => ['name' => 'Reativação fria', 'description' => 'Reaquecer leads', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, ainda procurando uma agência de marketing? Estamos com vagas!', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, separei um diagnóstico gratuito do seu site. Quer ver?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => 'Última tentativa, {{name}}. Posso ajudar com algo?', 'media_type' => 'text']],
                ],
            ],

            // ── SAAS & TECH ──────────────────────────────────────────────
            [
                'slug' => 'saas_trial_onboarding', 'category' => 'saas_tech',
                'name' => 'Trial onboarding (7 mensagens)',
                'description' => '7 mensagens em 14 dias para ativar usuários do trial.',
                'icon' => 'bi-rocket-takeoff',
                'sequence' => ['name' => 'Trial onboarding', 'description' => 'Ativação de trial', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Bem-vindo ao [Sua SaaS], {{name}}! 🚀 Aqui está seu primeiro passo.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, conseguiu fazer login? Vou te mostrar a feature mais importante.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Dia 3 do trial, {{name}}. Já experimentou [feature X]?', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 5760, 'type' => 'message', 'config' => ['body' => 'Dica do dia, {{name}}: como economizar 2h por dia com nossa automação.', 'media_type' => 'text']],
                    ['position' => 5, 'delay_minutes' => 8640, 'type' => 'message', 'config' => ['body' => '{{name}}, vamos agendar uma call de 15 min pra te ajudar?', 'media_type' => 'text']],
                    ['position' => 6, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => 'Dia 10, {{name}}! Curtindo a experiência? Falta pouco pro fim do trial.', 'media_type' => 'text']],
                    ['position' => 7, 'delay_minutes' => 17280, 'type' => 'message', 'config' => ['body' => '{{name}}, último dia do trial. Vamos garantir seu acesso?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'saas_pre_conversao', 'category' => 'saas_tech',
                'name' => 'Pré-conversão',
                'description' => '4 mensagens próximas ao fim do trial pra converter.',
                'icon' => 'bi-credit-card',
                'sequence' => ['name' => 'Pré-conversão', 'description' => 'Conversão de trial', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, seu trial está acabando. Quer continuar com a gente?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 1440, 'type' => 'message', 'config' => ['body' => '{{name}}, oferta especial: 20% OFF no plano anual. Use o cupom UPGRADE20!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => '{{name}}, faltam 24h pra você não perder seus dados. Vamos garantir?', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => 'Última chance, {{name}}! Seu acesso encerra em algumas horas.', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'saas_winback_churn', 'category' => 'saas_tech',
                'name' => 'Win-back churn',
                'description' => '3 mensagens para tentar trazer de volta clientes que cancelaram.',
                'icon' => 'bi-arrow-counterclockwise',
                'sequence' => ['name' => 'Win-back churn', 'description' => 'Recuperar cancelados', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, sentimos sua falta! 😢 O que motivou o cancelamento?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 7200, 'type' => 'message', 'config' => ['body' => '{{name}}, lançamos novas features que talvez ajudem. Quer ver?', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, oferta especial pra te trazer de volta: 50% OFF nos 3 primeiros meses.', 'media_type' => 'text']],
                ],
            ],

            // ── B2C / VAREJO ─────────────────────────────────────────────
            [
                'slug' => 'b2c_boas_vindas_rapido', 'category' => 'b2c_varejo',
                'name' => 'Boas-vindas rápido (7 dias)',
                'description' => '3 mensagens em 7 dias para captar interesse rápido.',
                'icon' => 'bi-bag-heart',
                'sequence' => ['name' => 'Boas-vindas B2C', 'description' => 'Captação rápida', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Olá {{name}}! 🛍️ Que bom ter você por aqui. Confira nossas ofertas.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 4320, 'type' => 'message', 'config' => ['body' => '{{name}}, separei produtos que combinam com você!', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 10080, 'type' => 'message', 'config' => ['body' => '{{name}}, quer aproveitar nosso cupom de boas-vindas?', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'b2c_reativacao', 'category' => 'b2c_varejo',
                'name' => 'Reativação 14 dias',
                'description' => '4 mensagens em 14 dias para reativar leads inativos.',
                'icon' => 'bi-arrow-clockwise',
                'sequence' => ['name' => 'Reativação B2C', 'description' => 'Reativar inativos', 'channel' => 'whatsapp', 'exit_on_reply' => true, 'exit_on_stage_change' => true],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => '{{name}}, sentimos sua falta! 💔 Como você está?', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 2880, 'type' => 'message', 'config' => ['body' => 'Novidades chegaram, {{name}}! Confira o que separamos.', 'media_type' => 'text']],
                    ['position' => 3, 'delay_minutes' => 7200, 'type' => 'message', 'config' => ['body' => 'Cupom especial pra você, {{name}}: VOLTOU15 (15% OFF).', 'media_type' => 'text']],
                    ['position' => 4, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => 'Última oferta, {{name}}! Volte e aproveite.', 'media_type' => 'text']],
                ],
            ],
            [
                'slug' => 'b2c_aniversario', 'category' => 'b2c_varejo',
                'name' => 'Aniversário (2 mensagens)',
                'description' => 'Mensagem de parabéns + lembrete de cupom.',
                'icon' => 'bi-gift',
                'sequence' => ['name' => 'Aniversário B2C', 'description' => 'Mês do aniversariante', 'channel' => 'whatsapp', 'exit_on_reply' => false, 'exit_on_stage_change' => false],
                'steps' => [
                    ['position' => 1, 'delay_minutes' => 0, 'type' => 'message', 'config' => ['body' => 'Parabéns, {{name}}! 🎂 Como presente, 15% OFF no seu mês de aniversário. Use NIVER15.', 'media_type' => 'text']],
                    ['position' => 2, 'delay_minutes' => 14400, 'type' => 'message', 'config' => ['body' => '{{name}}, ainda dá tempo de aproveitar seu cupom de aniversário!', 'media_type' => 'text']],
                ],
            ],
        ];
    }

    // ── i18n helpers ────────────────────────────────────────────────────────

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
            if (isset($tr['sequence']['name']) && is_string($tr['sequence']['name']) && $tr['sequence']['name'] !== '') {
                $template['sequence']['name'] = $tr['sequence']['name'];
            }
            if (isset($tr['sequence']['description']) && is_string($tr['sequence']['description']) && $tr['sequence']['description'] !== '') {
                $template['sequence']['description'] = $tr['sequence']['description'];
            }
            // Sobrescrever bodies dos steps
            if (isset($tr['steps']) && is_array($tr['steps'])) {
                foreach ($template['steps'] as $stepIdx => &$step) {
                    if (isset($tr['steps'][$stepIdx]) && is_string($tr['steps'][$stepIdx]) && $tr['steps'][$stepIdx] !== '') {
                        $step['config']['body'] = $tr['steps'][$stepIdx];
                    }
                }
                unset($step);
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

        $path = lang_path("{$locale}/sequence_templates.php");
        if (! is_file($path)) {
            return $cache[$locale] = [];
        }

        $data = require $path;
        return $cache[$locale] = is_array($data) ? $data : [];
    }
}
