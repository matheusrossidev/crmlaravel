<?php

declare(strict_types=1);

return [

    // =========================================================================
    // INDEX PAGE (agents/index.blade.php)
    // =========================================================================

    'index_title'                  => 'Agente de IA',
    'index_heading'                => 'Agentes de IA',
    'tokens_exhausted_btn'         => 'Tokens esgotados',
    'new_agent'                    => 'Novo Agente',

    // Empty state
    'empty_title'                  => 'Nenhum agente criado ainda',
    'empty_description'            => 'Crie um agente de IA para responder automaticamente nos seus chats.',
    'empty_cta'                    => 'Criar primeiro agente',

    // Agent card
    'badge_active'                 => 'Ativo',
    'badge_inactive'               => 'Inativo',
    'badge_no_tokens'              => 'Sem tokens',
    'objective_sales'              => 'Vendas',
    'objective_support'            => 'Suporte',
    'objective_general'            => 'Geral',
    'channel_whatsapp'             => 'WhatsApp',
    'channel_instagram'            => 'Instagram',
    'channel_web_chat'             => 'Web Chat',
    'conversation_singular'        => 'conversa',
    'conversation_plural'          => 'conversas',
    'created_label'                => 'Criado:',

    // Card dropdown
    'action_edit'                  => 'Editar',
    'action_test'                  => 'Testar',
    'action_delete'                => 'Excluir',
    'toggle_activate'              => 'Ativar',
    'toggle_deactivate'            => 'Desativar',

    // Delete modal
    'delete_modal_title'           => 'Excluir agente?',
    'delete_modal_text'            => 'O agente será removido permanentemente.<br>Esta ação não pode ser desfeita.',
    'delete_modal_cancel'          => 'Cancelar',
    'delete_modal_confirm'         => 'Excluir',

    // Test chat sidebar
    'test_chat_title'              => 'Agente',
    'test_chat_subtitle'           => 'Teste de conversa simulada',
    'test_chat_close'              => 'Fechar',
    'test_chat_placeholder'        => 'Digite uma mensagem...',
    'test_chat_send'               => 'Enviar',
    'test_chat_reset'              => 'Reiniciar conversa',
    'test_chat_reset_msg'          => 'Conversa reiniciada. Digite uma mensagem para começar.',
    'test_chat_error_prefix'       => 'Erro: ',
    'test_chat_error_generic'      => 'Falha ao obter resposta.',
    'test_chat_error_connection'   => 'Erro de conexão.',

    // Toastr (index)
    'toast_agent_deleted'          => 'Agente excluído.',
    'toast_delete_error'           => 'Erro ao excluir.',

    // =========================================================================
    // TOKEN QUOTA SIDEBAR (inside index.blade.php)
    // =========================================================================

    'quota_sidebar_title'          => 'Tokens de IA',
    'quota_sidebar_subtitle'       => 'Consumo e pacotes de incremento',
    'quota_exhausted_title'        => 'Quota esgotada este mês',
    'quota_exhausted_text'         => 'Seu agente foi pausado automaticamente. Adicione mais tokens para reativá-lo.',
    'quota_usage_label'            => 'Uso do mês atual',
    'quota_tokens_used'            => ':count tokens usados',
    'quota_percent_limit'          => ':pct% do limite',
    'quota_limit_label'            => 'Limite: :count tokens/mês',
    'quota_chart_title'            => 'Consumo — últimos 7 dias',
    'quota_choose_pack'            => 'Escolha um pacote para continuar',
    'quota_billing_title'          => 'Dados para cobrança (primeira compra)',
    'quota_cpf_cnpj'               => 'CPF ou CNPJ',
    'quota_email_nf'               => 'Email para nota fiscal',
    'quota_pix_title'              => 'Pague via PIX',
    'quota_pix_copy'               => 'Copiar código PIX',
    'quota_open_invoice'           => 'Abrir fatura',
    'quota_reactivation_notice'    => 'Após o pagamento, seu agente será reativado automaticamente em até 5 minutos.',
    'quota_no_packs'               => 'Nenhum pacote disponível no momento.',
    'quota_no_packs_contact'       => 'Entre em contato com o suporte.',
    'quota_buy_btn'                => 'Comprar pacote selecionado',

    // Toastr (quota)
    'toast_select_pack'            => 'Selecione um pacote antes de continuar.',
    'toast_cpf_required'           => 'Informe o CPF ou CNPJ para continuar.',
    'toast_email_required'         => 'Informe um email válido.',
    'toast_billing_generated'      => 'Cobrança gerada! Efetue o pagamento via PIX.',
    'toast_billing_error'          => 'Erro ao gerar cobrança.',
    'toast_pix_copied'             => 'Código PIX copiado!',
    'toast_pix_copy_error'         => 'Não foi possível copiar automaticamente.',
    'toast_connection_error'       => 'Erro de conexão. Tente novamente.',
    'toast_processing'             => 'Processando...',

    // =========================================================================
    // CREATE WIZARD (agents/create.blade.php)
    // =========================================================================

    'create_title'                 => 'Novo Agente de IA',
    'wizard_back'                  => 'Voltar',
    'wizard_step_counter'          => 'Passo :current de :total',
    'wizard_skip'                  => 'Pular este passo',
    'wizard_skip_short'            => 'Pular',
    'wizard_next'                  => 'Próximo',
    'wizard_create_agent'          => 'Criar Agente',
    'wizard_creating'              => 'Criando…',

    // Step 1: Name
    'step1_question'               => 'Como chamar seu agente?',
    'step1_subtitle'               => 'Dê um nome que represente a identidade do agente.',
    'step1_placeholder'            => 'Ex: Ana, Victor, Bot de Vendas',

    // Step 2: Company
    'step2_question'               => 'Qual empresa vai usar esse agente?',
    'step2_subtitle'               => 'Opcional — usado para o agente se apresentar corretamente.',
    'step2_placeholder'            => 'Ex: Loja do João, Clínica Bem-Estar',

    // Step 3: Objective
    'step3_question'               => 'Qual o objetivo principal?',
    'step3_subtitle'               => 'Define o foco das respostas do agente.',
    'step3_sales'                  => 'Vendas',
    'step3_sales_desc'             => 'Captura leads e conduz negociações',
    'step3_support'                => 'Suporte',
    'step3_support_desc'           => 'Resolve dúvidas e problemas',
    'step3_general'                => 'Geral',
    'step3_general_desc'           => 'Atendimento sem foco específico',

    // Step 4: Communication style
    'step4_question'               => 'Como ele deve se comunicar?',
    'step4_subtitle'               => 'Define o tom das mensagens do agente.',
    'step4_formal'                 => 'Formal',
    'step4_formal_desc'            => 'Profissional e estruturado',
    'step4_normal'                 => 'Normal',
    'step4_normal_desc'            => 'Natural e cordial',
    'step4_casual'                 => 'Informal',
    'step4_casual_desc'            => 'Descontraído e amigável',

    // Step 5: Language
    'step5_question'               => 'Em qual idioma?',
    'step5_subtitle'               => 'Idioma padrão das respostas do agente.',
    'step5_pt'                     => 'Português',
    'step5_en'                     => 'Inglês',
    'step5_es'                     => 'Espanhol',

    // Step 6: Persona
    'step6_question'               => 'Descreva a personalidade do agente',
    'step6_subtitle'               => 'Como o agente deve se apresentar e se comportar?',
    'step6_placeholder'            => 'Ex: Você é Ana, uma assistente virtual da Loja do João. Você é simpática, paciente e sempre focada em ajudar o cliente a encontrar o produto ideal...',

    // Step 7: Behavior
    'step7_question'               => 'Regras de comportamento',
    'step7_subtitle'               => 'O que o agente DEVE e NÃO DEVE fazer?',
    'step7_placeholder'            => 'Ex: Sempre cumprimente o cliente pelo nome. Nunca forneça preços sem confirmar disponibilidade. Encaminhe reclamações graves para um humano...',

    // Step 8: Finish action
    'step8_question'               => 'Mensagem ao encerrar atendimento',
    'step8_subtitle'               => 'O que o agente deve dizer ao finalizar a conversa?',
    'step8_placeholder'            => 'Ex: Obrigado pelo contato! Se precisar de mais alguma coisa, é só chamar. Tenha um ótimo dia!',

    // Step 9: Knowledge base
    'step9_question'               => 'Base de conhecimento',
    'step9_subtitle'               => 'Informações sobre sua empresa, produtos, preços, políticas…',
    'step9_placeholder'            => "Produto A: R\$ 99,90, disponível em azul e vermelho.\nProduto B: R\$ 149,00, prazo de entrega 5 dias.\nPolítica de troca: 7 dias após a compra...",

    // Step 10: Channel
    'step10_question'              => 'Canal de atendimento',
    'step10_subtitle'              => 'Onde este agente vai operar?',
    'step10_whatsapp'              => 'WhatsApp',
    'step10_whatsapp_desc'         => 'Integração com WAHA / WhatsApp Web',
    'step10_web_chat'              => 'Web Chat',
    'step10_web_chat_desc'         => 'Widget no site da empresa',

    // Step 11: Review
    'step11_question'              => 'Tudo certo? Revise antes de criar',
    'step11_subtitle'              => 'Confirme as informações do agente.',
    'review_empty'                 => 'Nenhum campo preenchido.',

    // Review labels
    'review_name'                  => 'Nome',
    'review_company'               => 'Empresa',
    'review_objective'             => 'Objetivo',
    'review_style'                 => 'Estilo',
    'review_language'              => 'Idioma',
    'review_persona'               => 'Personalidade',
    'review_behavior'              => 'Regras',
    'review_finish_action'         => 'Encerramento',
    'review_knowledge'             => 'Base de conhecimento',
    'review_channel'               => 'Canal',

    // Toastr (create wizard)
    'toast_name_required'          => 'Por favor, dê um nome ao agente.',
    'toast_objective_required'     => 'Selecione o objetivo do agente.',
    'toast_style_required'         => 'Selecione o estilo de comunicação.',
    'toast_language_required'      => 'Selecione o idioma.',
    'toast_channel_required'       => 'Selecione o canal de atendimento.',
    'toast_agent_created'          => 'Agente criado! Redirecionando para edição…',
    'toast_create_error'           => 'Erro ao criar o agente. Tente novamente.',
    'toast_connection_error_create' => 'Erro de conexão. Verifique sua internet e tente novamente.',

    // =========================================================================
    // ONBOARDING WIZARD (agents/onboarding.blade.php)
    // =========================================================================

    'onboarding_title'             => 'Novo Agente de IA',

    // Step 1: Name
    'ob_step1_question'            => 'Como quer chamar seu agente?',
    'ob_step1_subtitle'            => 'Um nome que identifique o agente (ex: Ana Vendas, Suporte Bot).',
    'ob_step1_placeholder'         => 'Ex: Ana, Assistente Comercial...',

    // Step 2: Company
    'ob_step2_question'            => 'Qual a empresa?',
    'ob_step2_subtitle'            => 'O agente vai se apresentar representando esta empresa.',
    'ob_step2_placeholder'         => 'Ex: Loja do João, Clínica Saúde Total...',

    // Step 3: Objective
    'ob_step3_question'            => 'Qual o objetivo do agente?',
    'ob_step3_subtitle'            => 'Isso define o comportamento base das respostas.',
    'ob_step3_sales'               => 'Vendas',
    'ob_step3_sales_desc'          => 'Qualificar leads e fechar negócios',
    'ob_step3_support'             => 'Suporte',
    'ob_step3_support_desc'        => 'Atender dúvidas e resolver problemas',
    'ob_step3_general'             => 'Geral',
    'ob_step3_general_desc'        => 'Atendimento versátil e informativo',

    // Step 4: Style
    'ob_step4_question'            => 'Estilo de comunicação',
    'ob_step4_subtitle'            => 'Como o agente deve se comunicar com os contatos.',
    'ob_step4_formal'              => 'Formal',
    'ob_step4_formal_desc'         => 'Profissional e objetivo',
    'ob_step4_normal'              => 'Normal',
    'ob_step4_normal_desc'         => 'Natural e cordial',
    'ob_step4_casual'              => 'Casual',
    'ob_step4_casual_desc'         => 'Descontraído e amigável',

    // Step 5: Language
    'ob_step5_question'            => 'Idioma de resposta',
    'ob_step5_subtitle'            => 'Em qual idioma o agente deve responder.',
    'ob_step5_pt'                  => 'Português',
    'ob_step5_en'                  => 'English',
    'ob_step5_es'                  => 'Español',

    // Step 6: Persona
    'ob_step6_question'            => 'Persona do agente',
    'ob_step6_subtitle'            => 'Descreva a personalidade e perfil do atendente virtual.',
    'ob_step6_placeholder'         => 'Ex: Sou a Ana, consultora de vendas com 5 anos de experiência no mercado imobiliário. Sou simpática, atenciosa e sempre busco entender as necessidades do cliente...',

    // Step 7: Behavior
    'ob_step7_question'            => 'Regras de comportamento',
    'ob_step7_subtitle'            => 'Defina o que o agente DEVE e NÃO DEVE fazer.',
    'ob_step7_placeholder'         => 'Ex: DEVE sempre perguntar o nome do cliente. NÃO DEVE dar descontos sem aprovação. DEVE transferir para humano quando o cliente ficar irritado...',

    // Step 8: Finish action
    'ob_step8_question'            => 'Mensagem de finalização',
    'ob_step8_subtitle'            => 'O que o agente deve dizer ao encerrar o atendimento.',
    'ob_step8_placeholder'         => 'Ex: Obrigado pelo contato! Se tiver mais dúvidas, é só chamar.',

    // Step 9: Knowledge
    'ob_step9_question'            => 'Base de conhecimento',
    'ob_step9_subtitle'            => 'Cole aqui informações sobre sua empresa, produtos, preços, FAQ, etc.',
    'ob_step9_placeholder'         => 'Ex: Nossa empresa oferece planos a partir de R$ 49/mês. Horário de funcionamento: segunda a sexta, 9h-18h. Endereço: Rua...',

    // Step 10: Media
    'ob_step10_question'           => 'Mídias para envio',
    'ob_step10_subtitle'           => 'Envie imagens, PDFs e catálogos que o agente pode enviar aos contatos durante a conversa.',
    'ob_step10_dropzone'           => 'Clique ou arraste arquivos aqui',
    'ob_step10_dropzone_hint'      => 'PNG, JPG, PDF, DOC — máx. 20 MB',
    'ob_step10_desc_placeholder'   => 'Descreva quando enviar (ex: catálogo de produtos, tabela de preços)',
    'ob_step10_upload_btn'         => 'Enviar',

    // Step 11: Channel
    'ob_step11_question'           => 'Canal de atendimento',
    'ob_step11_subtitle'           => 'Onde o agente vai atuar.',
    'ob_step11_whatsapp'           => 'WhatsApp',
    'ob_step11_whatsapp_desc'      => 'Atendimento via WhatsApp',
    'ob_step11_web_chat'           => 'Web Chat',
    'ob_step11_web_chat_desc'      => 'Widget de chat no site',

    // Step 12: Review
    'ob_step12_question'           => 'Tudo certo? Revise antes de criar',
    'ob_step12_subtitle'           => 'Confirme as informações do seu agente.',

    // Onboarding review labels
    'ob_review_name'               => 'Nome',
    'ob_review_company'            => 'Empresa',
    'ob_review_objective'          => 'Objetivo',
    'ob_review_style'              => 'Estilo',
    'ob_review_language'           => 'Idioma',
    'ob_review_persona'            => 'Persona',
    'ob_review_behavior'           => 'Comportamento',
    'ob_review_finish_action'      => 'Finalização',
    'ob_review_knowledge'          => 'Conhecimento',
    'ob_review_channel'            => 'Canal',
    'ob_review_media'              => 'Mídias',
    'ob_review_media_files'        => ':count arquivo(s): :names',

    // Toastr (onboarding)
    'ob_toast_name_required'       => 'Dê um nome ao seu agente.',
    'ob_toast_preparing'           => 'Preparando...',
    'ob_toast_prepare_error'       => 'Erro ao preparar agente.',
    'ob_toast_agent_created'       => 'Agente criado com sucesso!',
    'ob_toast_finalizing'          => 'Finalizando...',
    'ob_toast_file_too_large'      => 'Arquivo muito grande (máx. 20 MB).',
    'ob_toast_describe_file'       => 'Descreva quando o agente deve enviar este arquivo.',
    'ob_toast_file_uploaded'       => 'Arquivo enviado!',
    'ob_toast_file_error'          => 'Erro ao enviar arquivo.',
    'ob_toast_remove_confirm'      => 'Remover este arquivo?',
    'ob_toast_sending'             => 'Enviando...',

    // =========================================================================
    // FORM PAGE (agents/form.blade.php) — Edit / Create
    // =========================================================================

    'form_title'                   => 'Inteligência Artificial',
    'form_heading_edit'            => 'Editar Agente',
    'form_heading_create'          => 'Novo Agente',

    // Channel selector
    'channel_label'                => 'Canal de atuação',

    // Toggle: active
    'toggle_active_on'             => 'Agente Ativo',
    'toggle_active_off'            => 'Agente Inativo',
    'toggle_active_desc'           => 'Ativar para que responda automaticamente',

    // Toggle: auto-assign
    'toggle_auto_assign_on'        => 'Auto-assign Ativado',
    'toggle_auto_assign_off'       => 'Auto-assign Desativado',
    'toggle_auto_assign_desc'      => 'Atribuir automaticamente a novas conversas WhatsApp',

    // WhatsApp instances
    'wa_instances_title'           => 'Instâncias WhatsApp',
    'wa_instances_hint'            => 'Selecione quais números este agente atende. Se nenhum for selecionado, atende todos.',

    // ── Section 1: Identity ──
    's1_title'                     => '1. Identidade',
    's1_name'                      => 'Nome do Agente *',
    's1_name_placeholder'          => 'Ex: Assistente de Vendas',
    's1_company'                   => 'Nome da Empresa',
    's1_company_placeholder'       => 'Sua Empresa Ltda.',
    's1_objective'                 => 'Objetivo *',
    's1_objective_sales'           => 'Vendas',
    's1_objective_support'         => 'Suporte',
    's1_objective_general'         => 'Geral',
    's1_communication'             => 'Comunicação *',
    's1_style_formal'              => 'Formal',
    's1_style_normal'              => 'Normal',
    's1_style_casual'              => 'Descontraído',
    's1_language'                  => 'Idioma *',
    's1_lang_pt'                   => 'Português (BR)',
    's1_lang_en'                   => 'English',
    's1_lang_es'                   => 'Español',
    's1_industry'                  => 'Setor / Indústria',
    's1_industry_placeholder'      => 'Ex: E-commerce, SaaS, Saúde...',

    // ── Section 2: Persona ──
    's2_title'                     => '2. Persona e Comportamento',
    's2_persona'                   => 'Descrição da Persona',
    's2_persona_placeholder'       => 'Ex: Você é Maria, uma consultora de vendas simpática e proativa que adora ajudar clientes a encontrar a solução certa...',
    's2_behavior'                  => 'Comportamento',
    's2_behavior_placeholder'      => 'Ex: Sempre pergunte o nome do cliente. Nunca ofereça descontos sem consultar primeiro. Priorize resolver o problema antes de vender...',

    // ── Section 3: Flow ──
    's3_title'                     => '3. Fluxo do Atendimento',
    's3_on_finish'                 => 'Ao Finalizar o Atendimento',
    's3_on_finish_placeholder'     => 'Ex: Agradeça o contato, ofereça avaliação de 1-5 estrelas e encerre com uma mensagem positiva.',
    's3_on_transfer'               => 'Quando Transferir para Humano',
    's3_on_transfer_placeholder'   => 'Ex: Se o cliente solicitar falar com atendente, peça desculpas pela demora e informe que um humano vai assumir em breve.',
    's3_on_invalid'                => 'Ao Receber Mensagem Inválida / Tentativa de Jailbreak',
    's3_on_invalid_placeholder'    => 'Ex: Informe que só pode ajudar com assuntos relacionados ao nosso serviço e ofereça opções válidas.',

    // ── Section 4: Conversation stages ──
    's4_title'                     => '4. Etapas da Conversa',
    's4_description'               => 'Defina as etapas que o agente deve seguir durante a conversa (opcional).',
    's4_stage_name_placeholder'    => 'Nome da etapa',
    's4_stage_desc_placeholder'    => 'Descrição (opcional)',
    's4_add_stage'                 => 'Adicionar etapa',

    // ── Section 5: Knowledge base ──
    's5_title'                     => '5. Base de Conhecimento',
    's5_description'               => 'Inclua informações sobre sua empresa, produtos, preços, FAQs, políticas, etc. O agente usará estas informações para responder.',
    's5_kb_placeholder'            => "Empresa: XYZ Tecnologia\nProdutos: Plano Básico R\$49/mês, Plano Pro R\$99/mês\nHorário: seg-sex 9h-18h\nTelefone: (11) 1234-5678\n...",

    // Knowledge files
    's5_files_title'               => 'Arquivos de Conhecimento',
    's5_files_description'         => 'Faça upload de PDFs, imagens ou arquivos de texto. O conteúdo será extraído automaticamente e usado pelo agente.',
    's5_dropzone_text'             => 'Clique ou arraste arquivos aqui',
    's5_dropzone_hint'             => 'PDF, TXT, CSV, PNG, JPG, WEBP — máx. 20 MB',
    's5_status_extracted'          => 'Extraído',
    's5_status_failed'             => 'Falhou',
    's5_status_pending'            => 'Pendente',
    's5_preview_btn'               => 'Ver prévia',
    's5_remove_btn'                => 'Remover',

    // Toastr (knowledge files)
    'toast_kb_upload_error'        => 'Erro ao fazer upload.',
    'toast_kb_uploading'           => 'Fazendo upload e extraindo conteúdo de',
    'toast_kb_processed'           => 'Arquivo processado com sucesso!',
    'toast_kb_extract_failed'      => 'Extração falhou. Veja o motivo na lista.',
    'toast_kb_delete_confirm'      => 'Remover ":name" da base de conhecimento?',
    'toast_kb_delete_error'        => 'Erro ao remover arquivo.',
    'toast_kb_deleted'             => 'Arquivo removido.',
    'toast_kb_network_error'       => 'Erro de rede. Tente novamente.',

    // ── Section 5b: Agent media ──
    's5b_title'                    => 'Mídias do Agente',
    's5b_description'              => 'Arquivos que o agente pode <strong>enviar ao contato</strong> durante a conversa (catálogos, fotos, PDFs). Diferente da Base de Conhecimento, que é apenas para contexto interno.',
    's5b_dropzone_text'            => 'Clique ou arraste arquivos aqui',
    's5b_dropzone_hint'            => 'PNG, JPG, PDF, DOC — máx. 20 MB',
    's5b_no_description'           => 'Sem descrição',
    's5b_desc_placeholder'         => 'Descreva quando o agente deve enviar este arquivo',
    's5b_cancel'                   => 'Cancelar',
    's5b_upload'                   => 'Enviar',
    's5b_uploading'                => 'Enviando...',

    // Toastr (media)
    'toast_media_too_large'        => 'Arquivo muito grande (máx. 20 MB).',
    'toast_media_describe'         => 'Descreva quando o agente deve enviar este arquivo.',
    'toast_media_uploaded'         => 'Arquivo enviado!',
    'toast_media_upload_error'     => 'Erro ao enviar.',
    'toast_media_delete_confirm'   => 'Remover ":name"?',
    'toast_media_deleted'          => 'Arquivo removido.',
    'toast_media_delete_error'     => 'Erro ao remover.',
    'toast_media_network_error'    => 'Erro de rede.',

    // ── Section 6: Tools ──
    's6_title'                     => '6. Ferramentas do Agente',

    // Pipeline tool
    's6_pipeline_on'               => 'Controle de Funil Ativado',
    's6_pipeline_off'              => 'Controle de Funil Desativado',
    's6_pipeline_desc'             => 'O agente pode mover o lead entre as etapas do funil automaticamente durante o atendimento',

    // Tags tool
    's6_tags_on'                   => 'Atribuição de Tags Ativada',
    's6_tags_off'                  => 'Atribuição de Tags Desativada',
    's6_tags_desc'                 => 'O agente pode adicionar tags à conversa automaticamente conforme o contexto',

    // Intent notify
    's6_intent_on'                 => 'Detecção de Intenção Ativada',
    's6_intent_off'                => 'Detecção de Intenção Desativada',
    's6_intent_desc'               => 'Notifica quando o agente identificar sinais claros de intenção de compra, agendamento ou fechamento',

    // Calendar tool
    's6_calendar_on'               => 'Agenda Google Calendar Ativada',
    's6_calendar_off'              => 'Agenda Google Calendar Desativada',
    's6_calendar_desc'             => 'O agente pode criar, reagendar e cancelar eventos no Google Calendar conforme a conversa',
    's6_calendar_select_label'     => 'Agenda do Google Calendar',
    's6_calendar_primary'          => 'Agenda principal (primary)',
    's6_calendar_hint'             => 'Selecione em qual agenda o agente criará eventos. As agendas são carregadas da conta Google conectada.',
    's6_calendar_reload'           => 'Recarregar lista',
    's6_calendar_instructions'     => 'Como o agente deve usar a agenda',
    's6_calendar_instructions_ph'  => 'Ex: Quando o usuário pedir para marcar uma reunião, verifique os eventos já agendados e crie o evento. Reuniões têm 1 hora de duração por padrão. Sempre confirme o horário com o usuário antes de criar.',
    's6_calendar_integrations'     => 'O agente receberá estas instruções no prompt. Certifique-se de ter conectado o Google Calendar em',
    's6_calendar_integrations_link' => 'Configurações → Integrações',
    's6_calendar_loading'          => 'Carregando...',
    's6_calendar_principal'        => '(principal)',

    // Products tool
    's6_products_on'               => 'Catálogo de Produtos Ativado',
    's6_products_off'              => 'Catálogo de Produtos Desativado',
    's6_products_desc'             => 'O agente consulta preços, envia fotos/vídeos dos produtos e vincula itens ao lead automaticamente',

    // Transfer department
    's6_transfer_department'       => 'Transferir para departamento',
    's6_transfer_dept_none'        => '— Nenhum —',
    's6_transfer_dept_hint'        => 'Se definido, ao transferir para humano a conversa será encaminhada ao departamento (com distribuição automática). Tem prioridade sobre o usuário abaixo.',

    // Transfer user
    's6_transfer_user'             => 'Atribuir conversa a usuário (ao transferir)',
    's6_transfer_user_none'        => '— Nenhum (sem atribuição automática) —',
    's6_transfer_user_hint'        => 'Fallback: se nenhum departamento for definido, a conversa será atribuída a este usuário e o IA desativado.',

    // ── Section 7: Advanced ──
    's7_title'                     => '7. Configurações Avançadas',
    's7_max_message_length'        => 'Tamanho Máx. de Mensagem (caracteres)',
    's7_response_delay'            => 'Delay entre mensagens (segundos)',
    's7_response_delay_tooltip'    => 'Pausa entre cada parte da resposta (quando dividida em múltiplas mensagens)',
    's7_response_wait'             => 'Tempo de espera para batching (segundos)',
    's7_response_wait_tooltip'     => 'Aguardar X segundos antes de processar, para agrupar mensagens enviadas em sequência. 0 = sem espera.',
    's7_response_wait_desc'        => 'Quando o usuário manda várias mensagens seguidas, o agente aguarda este tempo antes de responder, processando todas juntas.',

    // ── Section 8: Follow-up ──
    's8_title'                     => '8. Follow-up Automático',
    's8_followup_on'               => 'Follow-up Ativado',
    's8_followup_off'              => 'Follow-up Desativado',
    's8_followup_desc'             => 'Quando o cliente para de responder, o agente retoma o contato automaticamente',
    's8_delay_minutes'             => 'Intervalo entre tentativas (minutos)',
    's8_delay_default'             => 'Padrão: 40 minutos',
    's8_max_count'                 => 'Máximo de tentativas por conversa',
    's8_max_count_hint'            => 'Após este limite a conversa é ignorada',
    's8_hour_start'                => 'Horário comercial — início (hora)',
    's8_hour_start_hint'           => 'Ex: 8 = a partir das 08:00',
    's8_hour_end'                  => 'Horário comercial — fim (hora)',
    's8_hour_end_hint'             => 'Ex: 18 = até as 18:59',

    // ── Section 9: Widget ──
    's9_title'                     => '9. Widget do Chat',
    's9_bot_name'                  => 'Nome do Bot',
    's9_bot_name_placeholder'      => 'Ex: Assistente Virtual',
    's9_bot_name_hint'             => 'Exibido no cabeçalho do widget',
    's9_widget_type'               => 'Tipo do Widget',
    's9_widget_bubble'             => 'Bubble',
    's9_widget_inline'             => 'Inline',
    's9_widget_type_hint'          => 'Bubble: botão flutuante. Inline: embutido na página.',
    's9_avatar'                    => 'Avatar do Bot',
    's9_avatar_hint'               => 'Escolha um avatar ou envie uma imagem personalizada.',
    's9_welcome_message'           => 'Mensagem de Boas-Vindas',
    's9_welcome_placeholder'       => 'Olá! Como posso te ajudar hoje?',
    's9_welcome_hint'              => 'Enviada automaticamente quando o visitante abre o chat.',
    's9_widget_color'              => 'Cor do Widget',
    's9_embed_code'                => 'Código de Incorporação',
    's9_embed_hint'                => 'Cole este código no HTML do seu site para exibir o widget de chat.',
    's9_embed_copy'                => 'Copiar',
    's9_embed_copied'              => 'Copiado!',

    // Form footer
    'form_save'                    => 'Salvar alterações',
    'form_create'                  => 'Criar Agente',
    'form_cancel'                  => 'Cancelar',
    'form_test_agent'              => 'Testar Agente',

    // Test chat (form page)
    'form_test_title'              => 'Testar:',
    'form_test_greeting'           => 'Olá! Sou :name. Como posso ajudar?',
    'form_test_placeholder'        => 'Digite uma mensagem...',
    'form_test_error'              => 'Erro: ',
    'form_test_error_generic'      => 'Falha ao obter resposta.',
    'form_test_error_connection'   => 'Erro de conexão.',

    // =========================================================================
    // CONFIG PAGE (ai/config.blade.php)
    // =========================================================================

    'config_title'                 => 'Agente de IA',
    'config_heading'               => 'Inteligência Artificial — Configuração',
    'config_subtitle'              => 'Configure o provedor de LLM para uso nos agentes de IA.',

    'config_provider_title'        => 'Provedor de LLM',
    'config_provider_subtitle'     => 'Escolha qual serviço de IA será usado pelos seus agentes.',
    'config_provider_label'        => 'Serviço',

    'config_api_key'               => 'API Key',
    'config_api_key_placeholder'   => 'Insira sua chave de API',
    'config_api_key_hint'          => 'A chave é armazenada de forma segura e nunca é exposta ao navegador.',
    'config_show_hide'             => 'Mostrar/ocultar',

    'config_model'                 => 'Modelo',

    'config_save'                  => 'Salvar',
    'config_test'                  => 'Testar conexão',
    'config_test_ok'               => 'Conexão OK',
    'config_test_api_key_warning'  => 'Insira a API key antes de testar.',
    'config_save_error'            => 'Erro ao salvar.',

    'config_next_step_title'       => 'Próximo passo',
    'config_next_step_text'        => 'Após configurar o provedor, acesse',
    'config_next_step_link'        => 'Agentes',
    'config_next_step_suffix'      => 'para criar seu primeiro agente de IA.',
];
