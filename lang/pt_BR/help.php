<?php

declare(strict_types=1);

return [
    'greeting' => 'Oi! Sou o assistente do Syncro. Como posso te ajudar?',
    'placeholder' => 'Digite sua duvida aqui...',
    'no_match' => 'Hmm, nao encontrei nada sobre isso. Tente reformular sua pergunta ou veja as categorias abaixo.',
    'contact_support' => 'Precisa de mais ajuda? Fale com nosso suporte em suporte@syncro.chat',
    'quick_actions' => [
        'Como criar um lead?',
        'Como conectar o WhatsApp?',
        'Como criar um chatbot?',
        'Como configurar um agente de IA?',
        'Como importar contatos?',
        'Como criar uma automacao?',
        'Como usar o pipeline Kanban?',
        'Como gerar relatorios?',
    ],
    'sections' => [

        // =====================================================================
        // 1. GETTING STARTED
        // =====================================================================
        'getting_started' => [
            'title' => 'Primeiros Passos',
            'articles' => [
                [
                    'question' => 'O que e o Syncro?',
                    'keywords' => ['syncro', 'plataforma', 'o que e', 'sobre', 'crm', 'para que serve', 'funcionalidades', 'sistema'],
                    'answer' => 'O Syncro e uma plataforma completa de CRM e marketing que reune pipeline de vendas, chat unificado (WhatsApp, Instagram e site), agentes de IA, chatbots, campanhas e automacoes em um so lugar. Tudo pensado para equipes de vendas e atendimento gerenciarem seus leads e conversas de forma eficiente.',
                ],
                [
                    'question' => 'Como comecar apos criar minha conta?',
                    'keywords' => ['comecar', 'inicio', 'primeiro acesso', 'configurar', 'setup', 'conta nova', 'primeiros passos', 'onboarding'],
                    'answer' => 'Apos criar sua conta, recomendamos: 1) Conecte seu WhatsApp em Configuracoes > Integracoes. 2) Configure seu pipeline de vendas em Configuracoes > Pipelines. 3) Adicione sua equipe em Configuracoes > Usuarios. 4) Crie seus primeiros leads manualmente ou importe uma planilha em CRM > Contatos.',
                ],
                [
                    'question' => 'Quais sao as principais funcionalidades?',
                    'keywords' => ['funcionalidades', 'recursos', 'features', 'o que faz', 'modulos', 'ferramentas', 'capacidades'],
                    'answer' => 'As principais funcionalidades sao: Pipeline Kanban para gestao de vendas, Chat Inbox unificado (WhatsApp + Instagram + Website), Agentes de IA com memoria, Chatbot builder visual, Automacoes por trigger, Campanhas com rastreamento UTM, Relatorios e dashboards, e Calendario integrado com Google Calendar.',
                ],
            ],
        ],

        // =====================================================================
        // 2. LEADS & CONTACTS
        // =====================================================================
        'leads' => [
            'title' => 'Leads e Contatos',
            'articles' => [
                [
                    'question' => 'Como criar um lead?',
                    'keywords' => ['criar lead', 'novo lead', 'adicionar lead', 'cadastrar lead', 'novo contato', 'adicionar contato', 'cadastrar contato', 'criar contato'],
                    'answer' => 'Va em CRM > Contatos e clique no botao "Novo Lead". Preencha os dados como nome, telefone, email e empresa. Voce tambem pode associar o lead a um pipeline e etapa especifica. Apos salvar, o lead aparecera tanto na lista de contatos quanto no Kanban do pipeline selecionado.',
                ],
                [
                    'question' => 'Como importar leads por Excel ou CSV?',
                    'keywords' => ['importar', 'excel', 'csv', 'planilha', 'upload', 'importacao', 'importar contatos', 'importar leads', 'massa', 'lote', 'bulk'],
                    'answer' => 'Acesse CRM > Contatos e clique em "Importar". Faca o upload de um arquivo Excel (.xlsx) ou CSV. O sistema vai mapear as colunas automaticamente — confira o mapeamento e confirme. Leads duplicados (mesmo telefone ou email) serao atualizados em vez de duplicados.',
                ],
                [
                    'question' => 'Como exportar leads?',
                    'keywords' => ['exportar', 'download', 'baixar', 'excel', 'csv', 'exportacao', 'exportar contatos', 'exportar leads', 'planilha'],
                    'answer' => 'Em CRM > Contatos, clique no botao "Exportar". O sistema vai gerar um arquivo Excel com todos os leads filtrados. Se voce aplicou filtros (por tag, etapa, data etc.), somente os leads visiveis serao exportados.',
                ],
                [
                    'question' => 'Como editar ou excluir um lead?',
                    'keywords' => ['editar lead', 'alterar lead', 'modificar lead', 'excluir lead', 'deletar lead', 'apagar lead', 'remover lead', 'editar contato', 'excluir contato'],
                    'answer' => 'Clique no lead na lista de contatos ou no Kanban para abrir o painel lateral. Ali voce pode editar qualquer campo clicando nele. Para excluir, clique no botao de exclusao (icone de lixeira) dentro do painel do lead. Atencao: a exclusao e permanente.',
                ],
                [
                    'question' => 'O que sao campos personalizados e como usar?',
                    'keywords' => ['campos personalizados', 'campos extras', 'custom fields', 'campo customizado', 'campos customizados', 'campo extra', 'campo adicional'],
                    'answer' => 'Campos personalizados permitem adicionar informacoes especificas do seu negocio aos leads (ex: CPF, data de nascimento, plano de interesse). Configure em Configuracoes > Campos Extras. Sao suportados 10 tipos: texto, numero, moeda, data, selecao, selecao multipla, checkbox, URL, telefone e email. Apos criados, os campos aparecem automaticamente no formulario do lead.',
                ],
                [
                    'question' => 'Como adicionar tags a um lead?',
                    'keywords' => ['tags', 'etiquetas', 'tag', 'etiqueta', 'adicionar tag', 'marcar lead', 'rotular', 'classificar', 'categorizar'],
                    'answer' => 'Abra o painel do lead clicando nele e use o campo de tags para adicionar ou remover etiquetas. As tags ajudam a classificar e filtrar seus leads. Voce pode gerenciar as tags disponiveis em Configuracoes > Tags. Tags tambem podem ser adicionadas automaticamente por chatbots e agentes de IA.',
                ],
                [
                    'question' => 'Como adicionar notas a um lead?',
                    'keywords' => ['notas', 'nota', 'anotacao', 'observacao', 'comentario', 'adicionar nota', 'registrar nota', 'anotacoes'],
                    'answer' => 'Abra o painel do lead e va na aba "Notas". Clique em "Nova Nota", digite o texto e salve. Todas as notas ficam registradas com data, hora e autor. Notas tambem podem ser criadas automaticamente por agentes de IA durante as conversas.',
                ],
            ],
        ],

        // =====================================================================
        // 3. CRM PIPELINE
        // =====================================================================
        'pipeline' => [
            'title' => 'Pipeline e Kanban',
            'articles' => [
                [
                    'question' => 'Como funciona o pipeline Kanban?',
                    'keywords' => ['kanban', 'pipeline', 'funil', 'funil de vendas', 'etapas', 'quadro', 'board', 'como funciona pipeline'],
                    'answer' => 'O pipeline Kanban exibe seus leads como cartoes organizados em colunas (etapas). Cada coluna representa uma fase do processo de vendas (ex: Novo, Qualificado, Proposta, Fechado). Voce pode arrastar e soltar os cartoes entre etapas para atualizar o progresso. Acesse em CRM > Pipeline.',
                ],
                [
                    'question' => 'Como mover um lead entre etapas?',
                    'keywords' => ['mover lead', 'arrastar', 'drag', 'mudar etapa', 'trocar etapa', 'mover etapa', 'drag and drop', 'arrastar e soltar'],
                    'answer' => 'No Kanban (CRM > Pipeline), basta arrastar o cartao do lead e soltar na coluna da nova etapa. Voce tambem pode alterar a etapa abrindo o painel do lead e selecionando a nova etapa no campo "Etapa". A mudanca e registrada automaticamente no historico do lead.',
                ],
                [
                    'question' => 'Como criar ou editar pipelines e etapas?',
                    'keywords' => ['criar pipeline', 'novo pipeline', 'editar pipeline', 'criar etapa', 'nova etapa', 'editar etapa', 'configurar pipeline', 'configurar etapas', 'personalizar funil'],
                    'answer' => 'Va em Configuracoes > Pipelines. Clique em "Novo Pipeline" para criar ou no icone de edicao para modificar um existente. Dentro de cada pipeline, voce pode adicionar, renomear, reordenar e excluir etapas. Arraste as etapas para mudar a ordem. Cada pipeline pode ter suas proprias etapas.',
                ],
                [
                    'question' => 'Como marcar uma venda como ganha ou perdida?',
                    'keywords' => ['venda ganha', 'venda perdida', 'ganhar', 'perder', 'fechar venda', 'won', 'lost', 'marcar ganho', 'marcar perdido', 'concluir venda'],
                    'answer' => 'Arraste o lead para a etapa marcada como "Ganho" ou "Perdido" no Kanban. Voce tambem pode abrir o lead e clicar nos botoes "Marcar como Ganho" ou "Marcar como Perdido". Ao marcar como perdido, o sistema solicita o motivo da perda para analise posterior.',
                ],
                [
                    'question' => 'O que sao motivos de perda?',
                    'keywords' => ['motivos de perda', 'razao perda', 'por que perdeu', 'motivo perdido', 'loss reason', 'motivos', 'razoes'],
                    'answer' => 'Motivos de perda sao categorias predefinidas que explicam por que um negocio nao foi fechado (ex: preco alto, foi para concorrente, sem resposta). Configure-os em Configuracoes > Motivos de Perda. Quando um lead e marcado como perdido, o vendedor seleciona o motivo. Isso gera dados valiosos para analise nos relatorios.',
                ],
            ],
        ],

        // =====================================================================
        // 4. WHATSAPP
        // =====================================================================
        'whatsapp' => [
            'title' => 'WhatsApp',
            'articles' => [
                [
                    'question' => 'Como conectar o WhatsApp?',
                    'keywords' => ['conectar whatsapp', 'whatsapp', 'integrar whatsapp', 'qr code', 'vincular whatsapp', 'configurar whatsapp', 'waha', 'instancia whatsapp'],
                    'answer' => 'Va em Configuracoes > Integracoes e clique em "WhatsApp". Clique em "Conectar Instancia" e escaneie o QR Code com seu celular (WhatsApp > Dispositivos Vinculados > Vincular Dispositivo). Apos a leitura, a conexao sera estabelecida automaticamente e as conversas comecam a chegar no inbox.',
                ],
                [
                    'question' => 'Como enviar mensagens pelo WhatsApp?',
                    'keywords' => ['enviar mensagem', 'mandar mensagem', 'enviar whatsapp', 'responder whatsapp', 'chat whatsapp', 'mensagem whatsapp', 'escrever mensagem'],
                    'answer' => 'Acesse Chats > WhatsApp e selecione uma conversa. Digite sua mensagem na caixa de texto e pressione Enter ou clique em Enviar. Voce tambem pode enviar imagens, documentos e audios. Para iniciar uma nova conversa, clique em "Nova Mensagem" e informe o numero do contato.',
                ],
                [
                    'question' => 'Como atribuir conversas a usuarios ou departamentos?',
                    'keywords' => ['atribuir conversa', 'designar conversa', 'transferir conversa', 'departamento', 'atribuir usuario', 'transferir atendimento', 'distribuir conversa', 'encaminhar'],
                    'answer' => 'Dentro de uma conversa no inbox, clique no icone de atribuicao no topo. Voce pode atribuir a um usuario especifico ou a um departamento. Departamentos podem ter estrategia de distribuicao automatica (round-robin ou menos ocupado), configuravel em Configuracoes > Departamentos.',
                ],
                [
                    'question' => 'Como importar o historico do WhatsApp?',
                    'keywords' => ['importar historico', 'historico whatsapp', 'mensagens antigas', 'conversas antigas', 'importar mensagens', 'historico conversas'],
                    'answer' => 'Apos conectar o WhatsApp, va em Configuracoes > Integracoes > WhatsApp e clique em "Importar Historico". O sistema buscara as conversas e mensagens recentes do WAHA. Esse processo pode levar alguns minutos dependendo do volume. Voce sera notificado quando a importacao terminar.',
                ],
                [
                    'question' => 'O que e o widget de botao do WhatsApp?',
                    'keywords' => ['widget whatsapp', 'botao whatsapp', 'botao flutuante', 'widget', 'botao site', 'whatsapp no site', 'chat widget'],
                    'answer' => 'O widget e um botao flutuante de WhatsApp que voce pode adicionar ao seu site. Quando o visitante clica, abre uma conversa direta no WhatsApp com seu numero. Configure o widget em Configuracoes > Integracoes > Widget WhatsApp, personalize a mensagem inicial e copie o codigo para inserir no seu site.',
                ],
            ],
        ],

        // =====================================================================
        // 5. INSTAGRAM
        // =====================================================================
        'instagram' => [
            'title' => 'Instagram',
            'articles' => [
                [
                    'question' => 'Como conectar o Instagram?',
                    'keywords' => ['conectar instagram', 'instagram', 'integrar instagram', 'vincular instagram', 'configurar instagram', 'facebook instagram', 'instagram business'],
                    'answer' => 'Va em Configuracoes > Integracoes e clique em "Instagram". Voce sera redirecionado para o Facebook para autorizar o acesso. E necessario ter uma conta Instagram Business vinculada a uma pagina do Facebook. Apos autorizar, as DMs do Instagram aparecerao no inbox de chats.',
                ],
                [
                    'question' => 'Como funcionam as automacoes de Instagram?',
                    'keywords' => ['automacao instagram', 'auto reply instagram', 'resposta automatica instagram', 'comentarios instagram', 'dm automatica', 'automacao comentario', 'instagram automation'],
                    'answer' => 'As automacoes de Instagram permitem responder automaticamente a comentarios em posts especificos. Voce define palavras-chave de ativacao — quando alguem comenta com essas palavras, o sistema pode: responder publicamente no comentario, enviar uma DM privada, ou ambos. Isso e otimo para campanhas como "comente QUERO para receber o link".',
                ],
                [
                    'question' => 'Como criar uma automacao de Instagram?',
                    'keywords' => ['criar automacao instagram', 'nova automacao instagram', 'configurar automacao instagram', 'setup instagram', 'regra instagram'],
                    'answer' => 'Acesse Configuracoes > Automacoes Instagram e clique em "Nova Automacao". Selecione o post, defina as palavras-chave de ativacao, configure a resposta no comentario e/ou a mensagem de DM. Voce pode ter multiplas automacoes ativas simultaneamente para diferentes posts e campanhas.',
                ],
            ],
        ],

        // =====================================================================
        // 6. CHATBOT
        // =====================================================================
        'chatbot' => [
            'title' => 'Chatbot',
            'articles' => [
                [
                    'question' => 'Como criar um fluxo de chatbot?',
                    'keywords' => ['criar chatbot', 'novo chatbot', 'criar fluxo', 'novo fluxo', 'chatbot', 'bot', 'fluxo automatico', 'builder chatbot', 'construir chatbot'],
                    'answer' => 'Va em Chatbot > Fluxos e clique em "Novo Fluxo". Escolha o canal (WhatsApp, Instagram ou Website). No builder visual, arraste nos do painel lateral para a area de trabalho. Conecte os nos para definir o fluxo da conversa. Comece com um no de mensagem, adicione perguntas e acoes conforme necessario.',
                ],
                [
                    'question' => 'Quais tipos de nos estao disponiveis no chatbot?',
                    'keywords' => ['tipos de nos', 'node types', 'blocos chatbot', 'mensagem', 'pergunta', 'condicao', 'acao', 'delay', 'fim', 'tipos blocos'],
                    'answer' => 'Os tipos disponiveis sao: Mensagem (envia texto ou imagem), Pergunta (faz uma pergunta com opcoes de resposta), Condicao (avalia uma variavel para direcionar o fluxo), Acao (executa acoes como mudar etapa, adicionar tag, transferir para humano ou enviar webhook), Delay (pausa por N segundos) e Fim (mensagem final que encerra o fluxo).',
                ],
                [
                    'question' => 'Como usar variaveis no chatbot?',
                    'keywords' => ['variaveis chatbot', 'variaveis', 'variavel', 'dados chatbot', 'informacoes chatbot', 'capturar dados', 'interpolacao', 'template'],
                    'answer' => 'Variaveis permitem capturar e reutilizar informacoes do usuario durante o fluxo. Quando voce cria um no de Pergunta, a resposta e salva em uma variavel (ex: {{nome}}). Use {{nome_da_variavel}} em qualquer mensagem para inserir o valor capturado. As variaveis ficam salvas durante toda a sessao do chatbot.',
                ],
                [
                    'question' => 'Como incorporar o chatbot no meu site?',
                    'keywords' => ['embed chatbot', 'chatbot site', 'widget chatbot', 'incorporar chatbot', 'chatbot website', 'instalar chatbot', 'codigo chatbot', 'script chatbot'],
                    'answer' => 'Crie um fluxo com canal "Website". Apos salvar, va na configuracao do fluxo e copie o codigo de incorporacao (snippet JavaScript). Cole esse codigo antes do fechamento da tag </body> no HTML do seu site. O widget de chat aparecera automaticamente para seus visitantes.',
                ],
                [
                    'question' => 'Como testar um chatbot?',
                    'keywords' => ['testar chatbot', 'teste chatbot', 'preview chatbot', 'simular chatbot', 'verificar chatbot', 'debug chatbot'],
                    'answer' => 'No builder do chatbot, clique no botao "Testar" para abrir o simulador. Voce pode interagir com o fluxo como se fosse um usuario final. Para testar no WhatsApp, atribua o fluxo a uma conversa de teste e envie uma mensagem com a palavra-chave de ativacao do fluxo.',
                ],
            ],
        ],

        // =====================================================================
        // 7. AI AGENTS
        // =====================================================================
        'ai_agent' => [
            'title' => 'Agentes de IA',
            'articles' => [
                [
                    'question' => 'Como criar um agente de IA?',
                    'keywords' => ['criar agente', 'novo agente', 'agente ia', 'inteligencia artificial', 'ia', 'ai agent', 'criar ia', 'configurar ia', 'bot ia'],
                    'answer' => 'Va em IA > Agentes e clique em "Novo Agente". Defina o nome, objetivo, estilo de comunicacao e persona do agente. Configure a base de conhecimento com informacoes sobre seu negocio. Apos salvar, voce pode atribuir o agente a conversas do WhatsApp ou Instagram para que ele atenda automaticamente.',
                ],
                [
                    'question' => 'Como configurar a base de conhecimento do agente?',
                    'keywords' => ['base de conhecimento', 'knowledge base', 'conhecimento ia', 'treinar ia', 'ensinar ia', 'documentos ia', 'arquivos ia', 'informacoes agente'],
                    'answer' => 'Na edicao do agente, va na secao "Base de Conhecimento". Voce pode digitar informacoes diretamente no campo de texto (FAQ, regras, dados do negocio) e tambem fazer upload de arquivos (PDF, TXT, DOCX). O agente usara todo esse conteudo para responder perguntas de forma precisa e contextualizada.',
                ],
                [
                    'question' => 'Quais sao as ferramentas (tools) do agente de IA?',
                    'keywords' => ['tools ia', 'ferramentas ia', 'pipeline tool', 'tags tool', 'calendar tool', 'intencao', 'intent', 'ferramentas agente', 'capacidades ia'],
                    'answer' => 'Os agentes possuem ferramentas opcionnais: Pipeline (move leads entre etapas automaticamente), Tags (adiciona/remove etiquetas), Deteccao de Intencao (alerta a equipe sobre sinais de compra), Calendario (consulta/cria agendamentos no Google Calendar) e Resposta por Voz (envia audios). Ative cada ferramenta na edicao do agente.',
                ],
                [
                    'question' => 'Como funciona o follow-up automatico?',
                    'keywords' => ['follow up', 'followup', 'seguimento', 'acompanhamento', 'recontato', 'mensagem automatica', 'lembrete', 'follow-up ia'],
                    'answer' => 'O follow-up automatico permite que o agente de IA recontate clientes que nao responderam apos um tempo definido. Configure o intervalo (ex: 24 horas) e o numero maximo de tentativas na edicao do agente. O agente enviara mensagens personalizadas tentando retomar a conversa de forma natural.',
                ],
                [
                    'question' => 'Como testar um agente de IA?',
                    'keywords' => ['testar agente', 'testar ia', 'teste ia', 'chat teste', 'simular ia', 'conversar com ia', 'preview ia'],
                    'answer' => 'Na pagina do agente, clique em "Testar Chat". Uma janela de conversa abrira para voce interagir diretamente com o agente usando suas configuracoes atuais. Isso permite validar respostas, tom de comunicacao e uso das ferramentas antes de colocar o agente em producao.',
                ],
                [
                    'question' => 'O que sao tokens de IA e como funciona a cobranca?',
                    'keywords' => ['tokens', 'creditos ia', 'cobranca ia', 'limite ia', 'quota', 'tokens esgotados', 'comprar tokens', 'pacote tokens', 'billing ia'],
                    'answer' => 'Tokens sao a unidade de consumo dos modelos de IA (cada mensagem enviada e recebida consome tokens). Seu plano inclui uma quantidade mensal de tokens. Quando o limite e atingido, os agentes pausam ate o proximo ciclo ou ate voce comprar um pacote adicional. Veja seu consumo em IA > Agentes e compre pacotes extras na mesma pagina.',
                ],
            ],
        ],

        // =====================================================================
        // 8. CAMPAIGNS & REPORTS
        // =====================================================================
        'campaigns' => [
            'title' => 'Campanhas e Relatorios',
            'articles' => [
                [
                    'question' => 'Como rastrear campanhas com UTM?',
                    'keywords' => ['utm', 'rastrear campanha', 'tracking', 'utm_source', 'utm_medium', 'utm_campaign', 'rastreamento', 'origem', 'fonte', 'campanha'],
                    'answer' => 'Adicione parametros UTM aos links das suas campanhas (utm_source, utm_medium, utm_campaign, utm_term, utm_content). Quando um lead chega pelo chatbot do site ou por um formulario, os UTMs sao capturados automaticamente. Voce pode ver a origem de cada lead no painel de detalhes e analisar o desempenho por campanha nos relatorios.',
                ],
                [
                    'question' => 'Como visualizar relatorios?',
                    'keywords' => ['relatorios', 'relatorio', 'dashboard', 'metricas', 'estatisticas', 'graficos', 'analytics', 'desempenho', 'resultados'],
                    'answer' => 'Acesse Campanhas > Relatorios para ver metricas de performance por campanha, canal e periodo. O dashboard principal tambem traz graficos de leads por etapa, vendas e atividade da equipe. Use os filtros de data e campanha para refinar a analise.',
                ],
                [
                    'question' => 'Como exportar relatorios em PDF?',
                    'keywords' => ['exportar pdf', 'pdf', 'relatorio pdf', 'baixar relatorio', 'imprimir relatorio', 'download relatorio', 'gerar pdf'],
                    'answer' => 'Na pagina de Relatorios, aplique os filtros desejados e clique no botao "Exportar PDF". O sistema gerara um arquivo PDF com os graficos e tabelas visiveis na tela. O arquivo sera baixado automaticamente no seu navegador.',
                ],
            ],
        ],

        // =====================================================================
        // 9. TASKS
        // =====================================================================
        'tasks' => [
            'title' => 'Tarefas',
            'articles' => [
                [
                    'question' => 'Como criar uma tarefa?',
                    'keywords' => ['criar tarefa', 'nova tarefa', 'adicionar tarefa', 'tarefa', 'task', 'atividade', 'to do', 'agendar tarefa'],
                    'answer' => 'Va em Tarefas e clique em "Nova Tarefa". Preencha o titulo, tipo, data/hora de vencimento e, opcionalmente, associe a um lead. Voce pode atribuir a tarefa a qualquer membro da equipe. Tarefas vencidas aparecem destacadas em vermelho para facil identificacao.',
                ],
                [
                    'question' => 'Quais tipos de tarefas existem?',
                    'keywords' => ['tipos de tarefa', 'tipo tarefa', 'ligacao', 'email', 'visita', 'reuniao', 'whatsapp tarefa', 'categorias tarefa'],
                    'answer' => 'Os tipos de tarefas disponiveis sao: Ligacao (call), E-mail, Tarefa generica, Visita, WhatsApp e Reuniao. Cada tipo tem um icone proprio para identificacao rapida. Escolha o tipo que melhor descreve a atividade ao criar uma nova tarefa.',
                ],
                [
                    'question' => 'Como visualizar tarefas no Kanban?',
                    'keywords' => ['kanban tarefas', 'quadro tarefas', 'board tarefas', 'visualizar tarefas', 'tarefas kanban', 'organizar tarefas'],
                    'answer' => 'Na pagina de Tarefas, alterne entre a visualizacao de lista e Kanban usando os botoes no topo. No modo Kanban, as tarefas sao organizadas por status (A Fazer, Em Andamento, Concluido). Arraste e solte para atualizar o status rapidamente.',
                ],
            ],
        ],

        // =====================================================================
        // 10. CALENDAR
        // =====================================================================
        'calendar' => [
            'title' => 'Calendario',
            'articles' => [
                [
                    'question' => 'Como conectar o Google Calendar?',
                    'keywords' => ['google calendar', 'calendario google', 'conectar calendario', 'integrar calendario', 'vincular google', 'sincronizar calendario', 'agenda google'],
                    'answer' => 'Va em Configuracoes > Integracoes e clique em "Google Calendar". Autorize o acesso com sua conta Google. Apos a conexao, seus eventos aparecerao no calendario do Syncro e voce podera criar novos eventos que sincronizam automaticamente com o Google Calendar.',
                ],
                [
                    'question' => 'Como criar ou editar eventos no calendario?',
                    'keywords' => ['criar evento', 'novo evento', 'editar evento', 'agendar', 'agendamento', 'marcar reuniao', 'calendario evento', 'compromisso'],
                    'answer' => 'Acesse Calendario e clique em um horario para criar um novo evento, ou clique em um evento existente para edita-lo. Preencha titulo, data, horario, descricao e, opcionalmente, associe a um lead. Eventos criados aqui sincronizam automaticamente com o Google Calendar se estiver conectado.',
                ],
                [
                    'question' => 'Como o agente de IA usa o calendario?',
                    'keywords' => ['ia calendario', 'agente calendario', 'agendar ia', 'ai calendar', 'consultar agenda', 'marcar horario ia', 'disponibilidade ia'],
                    'answer' => 'Quando a ferramenta de Calendario esta ativada no agente de IA, ele pode consultar horarios disponiveis e criar agendamentos automaticamente durante conversas com clientes. O agente verifica a disponibilidade no Google Calendar e sugere horarios ao cliente, tudo de forma natural na conversa.',
                ],
            ],
        ],

        // =====================================================================
        // 11. SETTINGS
        // =====================================================================
        'settings' => [
            'title' => 'Configuracoes',
            'articles' => [
                [
                    'question' => 'Como adicionar usuarios a equipe?',
                    'keywords' => ['adicionar usuario', 'novo usuario', 'convidar usuario', 'equipe', 'time', 'colaborador', 'membro', 'criar usuario', 'gerenciar usuarios'],
                    'answer' => 'Va em Configuracoes > Usuarios e clique em "Novo Usuario". Preencha nome, email e defina o nivel de acesso (Admin, Gestor ou Visualizador). O usuario recebera um email com as credenciais de acesso. Admins tem acesso total, Gestores podem gerenciar leads e conversas, e Visualizadores so podem consultar.',
                ],
                [
                    'question' => 'Como criar departamentos?',
                    'keywords' => ['departamento', 'setor', 'criar departamento', 'novo departamento', 'equipe departamento', 'area', 'divisao'],
                    'answer' => 'Acesse Configuracoes > Departamentos e clique em "Novo Departamento". Defina o nome e a estrategia de distribuicao de conversas (Round Robin ou Menos Ocupado). Adicione os membros do departamento. Conversas atribuidas ao departamento serao distribuidas automaticamente entre os membros conforme a estrategia escolhida.',
                ],
                [
                    'question' => 'Como gerenciar integracoes?',
                    'keywords' => ['integracoes', 'conectar', 'whatsapp', 'instagram', 'facebook', 'google', 'oauth', 'configurar integracao', 'vincular conta'],
                    'answer' => 'Em Configuracoes > Integracoes voce encontra todas as conexoes disponiveis: WhatsApp (via QR Code), Instagram (via Facebook OAuth), Google Calendar, Facebook Ads e Google Ads. Clique em cada integracao para conectar ou verificar o status da conexao. Integracoes ativas mostram um indicador verde.',
                ],
                [
                    'question' => 'Como alterar o idioma do sistema?',
                    'keywords' => ['idioma', 'lingua', 'portugues', 'ingles', 'language', 'mudar idioma', 'trocar idioma', 'traducao', 'pt-br', 'english'],
                    'answer' => 'Va em Configuracoes > Perfil e selecione o idioma desejado (Portugues ou English). A mudanca e aplicada imediatamente para toda a interface. Cada usuario pode escolher seu idioma de preferencia individualmente.',
                ],
                [
                    'question' => 'Como gerenciar assinatura e cobranca?',
                    'keywords' => ['assinatura', 'plano', 'cobranca', 'pagamento', 'fatura', 'billing', 'subscription', 'pix', 'cartao', 'cancelar', 'upgrade'],
                    'answer' => 'Acesse Configuracoes > Cobranca para ver seu plano atual, historico de faturas e metodo de pagamento. Voce pode fazer upgrade de plano, alterar o metodo de pagamento (PIX ou cartao) e visualizar o consumo de tokens de IA. Pagamentos sao processados via Asaas de forma segura.',
                ],
            ],
        ],

        // =====================================================================
        // 12. AUTOMATIONS
        // =====================================================================
        'automations' => [
            'title' => 'Automacoes',
            'articles' => [
                [
                    'question' => 'Como criar uma automacao?',
                    'keywords' => ['criar automacao', 'nova automacao', 'automacao', 'automatizar', 'regra automatica', 'workflow', 'fluxo automatico'],
                    'answer' => 'Va em Configuracoes > Automacoes e clique em "Nova Automacao". Escolha o trigger (gatilho), defina as condicoes e configure as acoes. Por exemplo: "Quando um lead mudar para etapa Proposta, enviar mensagem no WhatsApp e adicionar tag VIP". Ative a automacao e ela passara a funcionar automaticamente.',
                ],
                [
                    'question' => 'Quais triggers (gatilhos) estao disponiveis?',
                    'keywords' => ['triggers', 'gatilhos', 'gatilho', 'trigger', 'quando', 'evento', 'condicao disparo', 'tipos trigger'],
                    'answer' => 'Os triggers disponiveis incluem: mudanca de etapa no pipeline, criacao de lead, atualizacao de lead, tag adicionada/removida, conversa aberta/fechada, data especifica (ex: aniversario), mensagem recebida e venda fechada. Cada trigger pode ter condicoes adicionais para refinar quando a automacao deve ser executada.',
                ],
                [
                    'question' => 'Quais acoes posso configurar nas automacoes?',
                    'keywords' => ['acoes automacao', 'acoes', 'acao', 'enviar mensagem', 'mover lead', 'adicionar tag', 'webhook', 'notificar', 'acoes disponiveis'],
                    'answer' => 'As acoes disponiveis incluem: enviar mensagem no WhatsApp, mover lead para outra etapa, adicionar/remover tags, atribuir conversa a usuario/departamento, atribuir agente de IA, enviar webhook, criar nota no lead, enviar notificacao e atualizar campos do lead. Voce pode combinar multiplas acoes em uma unica automacao.',
                ],
            ],
        ],
    ],
];
