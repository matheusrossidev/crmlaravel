<?php

return [

    // ─── Index page ─────────────────────────────────────────────────────────
    'page_title'                   => 'Chatbot Builder',
    'new_flow'                     => 'Novo Fluxo',
    'empty_title'                  => 'Nenhum fluxo criado ainda',
    'empty_description'            => 'Crie um fluxo para atender seus contatos automaticamente no WhatsApp.',
    'empty_cta'                    => 'Criar primeiro fluxo →',
    'last_edit'                    => 'Última edição:',
    'created'                      => 'Criado:',
    'nodes_singular'               => 'nó',
    'nodes_plural'                 => 'nós',
    'attended'                     => 'atendidos',
    'activate'                     => 'Ativar',
    'deactivate'                   => 'Desativar',

    // Index card dropdown
    'dropdown_edit'                => 'Editar',
    'dropdown_results'             => 'Resultados',
    'dropdown_test'                => 'Testar',
    'dropdown_link'                => 'Link',
    'dropdown_embed'               => 'Embed',
    'dropdown_delete'              => 'Excluir',

    // Index badges
    'badge_active'                 => 'Ativo',
    'badge_inactive'               => 'Inativo',
    'badge_catch_all'              => 'Catch-all',

    // Index delete modal
    'delete_modal_title'           => 'Excluir fluxo?',
    'delete_modal_text'            => 'O fluxo <strong>:name</strong> será removido permanentemente.<br>Esta ação não pode ser desfeita.',
    'delete_modal_cancel'          => 'Cancelar',
    'delete_modal_confirm'         => 'Excluir',

    // Index toastr
    'toast_link_copied'            => 'Link copiado!',
    'toast_toggle_error'           => 'Erro ao alterar status do fluxo.',

    // ─── Test chat sidebar ──────────────────────────────────────────────────
    'test_title'                   => 'Testando fluxo',
    'test_subtitle'                => 'Simulação · nenhuma mensagem real enviada',
    'test_input_placeholder'       => 'Digite sua resposta…',
    'test_restart'                 => 'Reiniciar',
    'test_done'                    => 'Fluxo concluído',
    'test_hint'                    => 'Enter para enviar · Shift+Enter para nova linha',
    'test_server_error'            => 'Erro ao comunicar com o servidor.',

    // ─── Embed modal (index + edit + builder) ───────────────────────────────
    'embed_modal_title'            => 'Código de instalação',
    'embed_modal_paste_before'     => 'Cole este código antes do <code>&lt;/body&gt;</code> do seu site:',
    'embed_modal_paste_fullpage'   => 'Para exibir o chatbot em <strong>página inteira</strong>, cole o código abaixo no <code>&lt;body&gt;</code> da página dedicada:',
    'embed_modal_paste_inline'     => 'Para exibir o chatbot <strong>embutido na página</strong>, cole o código abaixo onde deseja que ele apareça:',
    'embed_modal_hint_fullpage'    => '<i class="bi bi-info-circle"></i> O <code>&lt;div id="syncro-chat"&gt;</code> ocupará toda a tela. Ideal para uma página dedicada ao chat (ex: <code>/atendimento</code>).',
    'embed_modal_hint_inline'      => '<i class="bi bi-info-circle"></i> O <code>&lt;div id="syncro-chat"&gt;</code> é o container do chatbot. Ajuste <code>width</code> e <code>height</code> conforme necessário.',
    'embed_modal_bubble_hint'      => 'O widget flutuante aparecerá no canto inferior direito:',
    'embed_copy_button'            => 'Copiar código',
    'embed_copied'                 => 'Copiado!',
    'embed_widget_hint'            => 'O widget aparecerá no canto inferior direito do seu site.',
    'embed_public_link'            => 'Link público',
    'embed_public_link_hint'       => 'Use este link em campanhas, redes sociais ou bio do Instagram. Não precisa ter site.',
    'embed_public_link_copied'     => 'Link copiado!',
    'embed_code_label'             => 'Código embed',
    'embed_copy'                   => 'Copiar',

    // ─── Form page (create/edit) ────────────────────────────────────────────
    'form_title_new'               => 'Novo Fluxo',
    'form_title_edit'              => 'Editar Fluxo',
    'form_back'                    => 'Voltar',

    // Form sections
    'form_section_channel'         => 'Canal',
    'form_section_identification'  => 'Identificação',
    'form_section_widget'          => 'Aparência do Widget',
    'form_section_trigger'         => 'Disparo Automático',
    'form_section_variables'       => 'Variáveis de Sessão',
    'form_section_status'          => 'Status',

    // Form fields
    'form_flow_name'               => 'Nome do fluxo',
    'form_flow_name_placeholder'   => 'Ex: Qualificação de Lead',
    'form_description'             => 'Descrição',
    'form_description_placeholder' => 'Para que serve este fluxo?',
    'form_slug'                    => 'Slug (URL pública)',
    'form_slug_placeholder'        => 'meu-chatbot',
    'form_bot_name'                => 'Nome do bot',
    'form_bot_name_placeholder'    => 'Ex: Ana, Sofia, Assistente...',
    'form_bot_name_hint'           => 'Aparece no cabeçalho do chat.',
    'form_bot_avatar'              => 'Avatar do bot',
    'form_bot_avatar_hint'         => 'Escolha um avatar ou clique no último ícone para fazer upload de uma imagem personalizada.',
    'form_welcome_message'         => 'Mensagem de entrada',
    'form_welcome_placeholder'     => 'Olá! 👋 Posso te ajudar?',
    'form_welcome_hint'            => 'Aparece como bolinha flutuante acima do botão do chat após 3 segundos. Deixe vazio para desativar. (Apenas no modo Bubble)',
    'form_widget_type'             => 'Tipo de Widget',
    'form_widget_bubble'           => 'Bubble',
    'form_widget_bubble_desc'      => 'Botão flutuante no canto da tela',
    'form_widget_inline'           => 'Inline / Página',
    'form_widget_inline_desc'      => 'Embebido em elemento da página',
    'form_bubble_hint'             => 'Adicione <code>&lt;script src="..." data-token="..."&gt;&lt;/script&gt;</code> antes de <code>&lt;/body&gt;</code>.',
    'form_inline_hint'             => 'Adicione <code>&lt;div id="syncro-chat"&gt;&lt;/div&gt;</code> onde deseja o chat, e o <code>&lt;script&gt;</code> no final do body.',
    'form_button_color'            => 'Cor dos botões',
    'form_button_color_hint'       => 'Define a cor do botão do chat, cabeçalho e balões de mensagem enviada.',
    'form_trigger_keywords'        => 'Keywords de disparo',
    'form_trigger_placeholder'     => 'oi, olá, bom dia',
    'form_trigger_hint'            => 'Separadas por vírgula. Deixe vazio para atribuição apenas manual. Quando um contato enviar uma mensagem contendo uma dessas palavras, o fluxo será iniciado automaticamente.',
    'form_variables'               => 'Variáveis',
    'form_variables_placeholder'   => 'nome, email, interesse',
    'form_variables_hint'          => 'Nomes das variáveis que o fluxo irá coletar. Ex: <code>nome</code> → use <code>{{nome}}</code> em mensagens.',
    'form_active_label'            => 'Fluxo ativo',
    'form_active_hint'             => 'Quando ativo, o fluxo responde automaticamente às mensagens.',

    // Form buttons
    'form_save_changes'            => 'Salvar alterações',
    'form_create_and_edit'         => 'Criar e editar nós',
    'form_cancel'                  => 'Cancelar',
    'form_open_builder'            => 'Abrir Builder de Nós',

    // ─── Edit page (builder wrapper) ────────────────────────────────────────
    'edit_builder_title'           => 'Builder:',
    'edit_back'                    => 'Voltar',
    'edit_flow_settings'           => 'Configurações do fluxo',
    'edit_all_flows'               => 'Todos os fluxos',
    'edit_embed'                   => 'Embed',
    'edit_embed_title'             => 'Código de Incorporação',
    'edit_embed_paste'             => 'Cole este código antes do <code>&lt;/body&gt;</code> no seu site:',
    'edit_copy'                    => 'Copiar',
    'edit_copied'                  => 'Copiado!',

    // ─── Builder page ───────────────────────────────────────────────────────

    // Builder header
    'builder_name_placeholder'     => 'Nome do fluxo...',
    'builder_active'               => 'Ativo',
    'builder_inactive'             => 'Inativo',
    'builder_test'                 => 'Testar',
    'builder_embed'                => 'Embed',
    'builder_config'               => 'Config',
    'builder_save'                 => 'Salvar',

    // Builder sidebar — blocks
    'sidebar_blocks'               => 'Blocos',
    'sidebar_message'              => 'Mensagem',
    'sidebar_input'                => 'Pergunta',
    'sidebar_condition'            => 'Condição',
    'sidebar_action'               => 'Ação',
    'sidebar_delay'                => 'Aguardar',
    'sidebar_end'                  => 'Fim',
    'sidebar_cards'                => 'Cards',

    // Builder sidebar — config
    'sidebar_config'               => 'Config',
    'sidebar_variables'            => 'Variáveis',
    'sidebar_catch_all'            => 'Fluxo padrão (catch-all)',
    'sidebar_catch_all_hint'       => 'Ativa quando nenhuma keyword corresponder. Apenas 1 por tenant.',

    // Builder sidebar — templates
    'sidebar_templates'            => 'Modelos',
    'sidebar_use_template'         => 'Usar modelo',

    // Builder node types
    'node_message'                 => 'Mensagem',
    'node_input'                   => 'Pergunta',
    'node_condition'               => 'Condição',
    'node_action'                  => 'Ação',
    'node_delay'                   => 'Aguardar',
    'node_end'                     => 'Fim',
    'node_cards'                   => 'Cards',
    'node_start'                   => 'Início',
    'node_start_desc'              => 'Quando visitante envia mensagem',

    // Builder node summaries
    'summary_empty_message'        => 'Mensagem vazia',
    'summary_empty_question'       => 'Pergunta vazia',
    'summary_condition_if'         => 'Se :variable...',
    'summary_empty_condition'      => 'Condição vazia',
    'summary_seconds'              => ':count segundos',
    'summary_finalize'             => 'Finalizar',
    'summary_cards_count'          => ':count card(s)',
    'summary_finalize_conversation'=> 'Finalizar conversa',

    // Builder preview
    'preview_image'                => 'Imagem',
    'preview_option'               => 'Opção',

    // Builder add step
    'add_block'                    => 'Adicionar bloco',
    'add_step'                     => 'Adicionar',

    // Builder edit panel — move/delete
    'panel_move_up'                => 'Cima',
    'panel_move_down'              => 'Baixo',

    // Builder edit panel — buttons section
    'panel_buttons'                => 'Botões',
    'panel_button_placeholder'     => 'Texto do botão',
    'panel_add_button'             => 'Adicionar botão',
    'panel_remove'                 => 'Remover',

    // ─── Message node ───────────────────────────────────────────────────────
    'msg_text_label'               => 'Texto da mensagem',
    'msg_text_placeholder'         => 'Digite a mensagem...',
    'msg_click_to_change_image'    => 'Clique para trocar a imagem',
    'msg_click_to_add_image'       => 'Clique para adicionar imagem',
    'msg_upload_image'             => 'Enviar imagem',

    // ─── Input node ─────────────────────────────────────────────────────────
    'input_question_label'         => 'Pergunta para o visitante',
    'input_question_placeholder'   => 'Digite a pergunta...',
    'input_field_type'             => 'Tipo do campo',
    'input_save_to'                => 'Salvar em',
    'input_save_none'              => 'Não salvar',
    'input_show_buttons'           => 'Exibir botões de resposta rápida',

    // Input field types
    'field_type_text'              => 'Texto livre',
    'field_type_name'              => 'Nome',
    'field_type_email'             => 'E-mail',
    'field_type_phone'             => 'Telefone',
    'field_type_number'            => 'Número',
    'field_type_buttons'           => 'Botões de resposta rápida',

    // ─── Condition node ─────────────────────────────────────────────────────
    'condition_variable_label'     => 'Variável a verificar',
    'condition_select'             => 'Selecione...',
    'condition_hint'               => 'Cada ramificação abaixo define uma condição. Ex: <strong>"Se :variable for igual a X, faça..."</strong>',

    // Condition operators
    'op_equals'                    => 'Igual a',
    'op_not_equals'                => 'Diferente',
    'op_contains'                  => 'Contém',
    'op_starts_with'               => 'Começa com',
    'op_ends_with'                 => 'Termina com',
    'op_gt'                        => 'Maior que',
    'op_lt'                        => 'Menor que',

    // Condition branch sentence fragments
    'op_sentence_equals'           => 'igual a',
    'op_sentence_not_equals'       => 'diferente de',
    'op_sentence_contains'         => 'contém',
    'op_sentence_starts_with'      => 'começa com',
    'op_sentence_ends_with'        => 'termina com',
    'op_sentence_gt'               => 'maior que',
    'op_sentence_lt'               => 'menor que',

    // ─── Action node ────────────────────────────────────────────────────────
    'action_type_label'            => 'Tipo da ação',

    // Action types
    'action_create_lead'           => 'Criar lead',
    'action_change_stage'          => 'Mover para etapa',
    'action_add_tag'               => 'Adicionar tag',
    'action_remove_tag'            => 'Remover tag',
    'action_save_variable'         => 'Salvar variável',
    'action_close_conversation'    => 'Encerrar conversa',
    'action_assign_human'          => 'Transferir para humano',
    'action_send_webhook'          => 'Enviar webhook',
    'action_set_custom_field'      => 'Preencher campo personalizado',
    'action_send_whatsapp'         => 'Enviar WhatsApp',
    'action_create_task'           => 'Criar tarefa',
    'action_redirect'              => 'Redirecionar (URL)',

    // Action: create lead
    'action_name'                  => 'Nome',
    'action_email'                 => 'Email',
    'action_phone'                 => 'Telefone',
    'action_stage'                 => 'Etapa',
    'action_select_variable'       => 'Selecione variável...',

    // Action: change stage
    'action_target_stage'          => 'Etapa destino',

    // Action: add/remove tag
    'action_tag'                   => 'Tag',

    // Action: save variable
    'action_variable'              => 'Variável',
    'action_value'                 => 'Valor',

    // Action: webhook
    'action_method'                => 'Método',
    'action_url'                   => 'URL',
    'action_json_body'             => 'JSON Body',

    // Action: set custom field
    'action_field'                 => 'Campo',
    'action_field_value'           => 'Valor',

    // Action: send whatsapp
    'action_destination'           => 'Destino',
    'action_phone_mode_variable'   => 'Variável do fluxo',
    'action_phone_mode_custom'     => 'Número fixo',
    'action_phone_variable'        => 'Variável com telefone',
    'action_phone_number'          => 'Número (com DDD)',
    'action_wa_message'            => 'Mensagem',
    'action_wa_hint'               => 'Enviada pela instância WhatsApp conectada.',

    // Action: create task
    'action_task_subject'          => 'Assunto da tarefa',
    'action_task_subject_placeholder' => 'Ligar para {{nome}}',
    'action_task_description'      => 'Descrição',
    'action_task_desc_placeholder' => 'Detalhes da tarefa...',
    'action_task_type'             => 'Tipo',
    'action_task_priority'         => 'Prioridade',
    'action_task_due_days'         => 'Prazo (dias)',
    'action_task_due_time'         => 'Horário',
    'action_task_assign_to'        => 'Atribuir a',
    'action_task_assign_auto'      => 'Automático (responsável do lead)',
    'action_task_assign_user'      => 'Usuário específico',
    'action_task_user'             => 'Usuário',
    'action_task_hint'             => 'Cria uma tarefa vinculada ao lead da conversa.',

    // Task types
    'task_type_call'               => 'Ligar',
    'task_type_email'              => 'Email',
    'task_type_task'               => 'Tarefa',
    'task_type_visit'              => 'Visita',
    'task_type_whatsapp'           => 'WhatsApp',
    'task_type_meeting'            => 'Reunião',

    // Task priorities
    'priority_low'                 => 'Baixa',
    'priority_medium'              => 'Média',
    'priority_high'                => 'Alta',

    // Action: redirect
    'action_redirect_url'          => 'URL de destino',
    'action_redirect_open_in'      => 'Abrir em',
    'action_redirect_new_tab'      => 'Nova aba',
    'action_redirect_same_tab'     => 'Mesma aba',
    'action_redirect_hint'         => 'Redireciona o visitante para a URL informada.',

    // ─── Delay node ─────────────────────────────────────────────────────────
    'delay_seconds_label'          => 'Segundos de espera',

    // ─── End node ───────────────────────────────────────────────────────────
    'end_message_label'            => 'Mensagem de encerramento (opcional)',
    'end_message_placeholder'      => 'Mensagem de encerramento...',

    // ─── Cards node ─────────────────────────────────────────────────────────
    'card_title_placeholder'       => 'Título',
    'card_description_placeholder' => 'Descrição',
    'card_button_placeholder'      => 'Texto do botão (opcional)',
    'card_button_action_reply'     => 'Continuar fluxo',
    'card_button_action_url'       => 'Abrir link',
    'card_url_placeholder'         => 'URL',
    'card_value_placeholder'       => 'Valor enviado',
    'card_remove'                  => 'Remover',
    'card_add'                     => 'Adicionar card',

    // ─── Branches ───────────────────────────────────────────────────────────
    'branch_option'                => 'Opção :number',
    'branch_default'               => 'Padrão',
    'branch_default_hint'          => 'Quando nenhuma opção corresponder',
    'branch_max_chars'             => 'Máx. 24 caracteres',
    'branch_remove'                => 'Remover opção',
    'branch_add'                   => 'Adicionar opção',
    'branch_operator'              => 'Operador',
    'branch_value'                 => 'Valor',
    'branch_condition_sentence'    => 'Se <strong>:variable</strong> :operator <strong>:value</strong>',

    // ─── Variables modal ────────────────────────────────────────────────────
    'vars_modal_title'             => 'Variáveis do fluxo',
    'vars_placeholder'             => 'nome_variavel',
    'vars_system_label'            => 'Variáveis de sistema:',
    'vars_hint_label'              => 'Variáveis:',
    'vars_insert_title'            => 'Inserir',

    // ─── Variables toastr ───────────────────────────────────────────────────
    'toast_var_exists'             => 'Variável já existe',

    // ─── Builder embed modal ────────────────────────────────────────────────
    'builder_embed_title'          => 'Código de instalação',
    'builder_embed_paste'          => 'Cole este código antes do <code>&lt;/body&gt;</code> do seu site:',
    'builder_embed_copy'           => 'Copiar código',
    'builder_embed_copied'         => 'Copiado!',

    // ─── Templates modal (builder) ──────────────────────────────────────────
    'tpl_modal_title'              => 'Modelos pré-prontos',
    'tpl_search_placeholder'       => 'Buscar por nicho... ex: dentista, restaurante, academia',
    'tpl_empty'                    => 'Nenhum modelo encontrado para esta busca.',
    'tpl_nodes'                    => 'nós',
    'tpl_variables'                => 'variáveis',
    'tpl_confirm_replace'          => 'Isso vai substituir todos os nós atuais. Deseja continuar?',
    'tpl_loaded'                   => 'Modelo ":name" carregado!',

    // Template categories
    'tpl_category_all'             => 'Todos',
    'tpl_category_geral'           => 'Geral',
    'tpl_category_imoveis'         => 'Imóveis',
    'tpl_category_saude'           => 'Saúde',
    'tpl_category_estetica'        => 'Estética',
    'tpl_category_fitness'         => 'Fitness',
    'tpl_category_educacao'        => 'Educação',
    'tpl_category_alimentacao'     => 'Alimentação',
    'tpl_category_varejo'          => 'Varejo',
    'tpl_category_servicos'        => 'Serviços',
    'tpl_category_automotivo'      => 'Automotivo',
    'tpl_category_tecnologia'      => 'Tecnologia',
    'tpl_category_eventos'         => 'Eventos',
    'tpl_category_turismo'         => 'Turismo',
    'tpl_category_financeiro'      => 'Financeiro',
    'tpl_category_construcao'      => 'Construção',

    // ─── Builder toastr messages ────────────────────────────────────────────
    'toast_flow_saved'             => 'Fluxo salvo com sucesso!',
    'toast_save_error'             => 'Erro ao salvar',
    'toast_save_flow_error'        => 'Erro ao salvar fluxo',
    'toast_name_required'          => 'Informe o nome do fluxo',
    'toast_upload_error'           => 'Erro ao enviar imagem',
    'toast_catch_all_on'           => 'Fluxo definido como catch-all',
    'toast_catch_all_off'          => 'Catch-all desativado',
    'toast_update_error'           => 'Erro ao atualizar',

    // ─── Onboarding wizard ──────────────────────────────────────────────────
    'onboarding_title'             => 'Novo Chatbot',
    'onboarding_step_counter'      => 'Passo :current de :total',
    'onboarding_back'              => 'Voltar',
    'onboarding_next'              => 'Próximo',
    'onboarding_create'            => 'Criar Chatbot',
    'onboarding_creating'          => 'Criando...',
    'onboarding_skip'              => 'Pular',

    // Wizard step: channel
    'wizard_channel_question'      => 'Para qual canal?',
    'wizard_channel_subtitle'      => 'Escolha onde seu chatbot vai operar.',
    'wizard_channel_whatsapp_desc' => 'Dispara por palavras-chave',
    'wizard_channel_instagram_desc'=> 'DMs e respostas automáticas',
    'wizard_channel_website_desc'  => 'Widget de chat no seu site',

    // Wizard step: name
    'wizard_name_question'         => 'Como quer chamar seu chatbot?',
    'wizard_name_subtitle'         => 'Um nome curto e descritivo.',
    'wizard_name_placeholder'      => 'Ex: Qualificador de Leads, Atendimento...',
    'wizard_description_label'     => 'DESCRIÇÃO',
    'wizard_description_placeholder'=> 'Descreva brevemente o objetivo deste fluxo...',

    // Wizard step: template
    'wizard_template_question'     => 'Escolha um modelo ou comece do zero',
    'wizard_template_subtitle'     => 'Modelos pré-prontos aceleram a criação.',
    'wizard_template_search'       => 'Buscar modelo...',
    'wizard_template_from_scratch' => 'Começar do zero',

    // Wizard step: widget settings
    'wizard_widget_question'       => 'Configure seu widget',
    'wizard_widget_subtitle'       => 'Personalize a aparência do chat no seu site.',
    'wizard_widget_bot_name'       => 'Nome do bot',
    'wizard_widget_bot_placeholder'=> 'Ex: Ana, Sofia, Assistente...',
    'wizard_widget_avatar'         => 'Avatar',
    'wizard_widget_upload'         => 'Upload personalizado',
    'wizard_widget_welcome'        => 'Mensagem de boas-vindas',
    'wizard_widget_welcome_placeholder' => 'Olá! 👋 Como posso te ajudar?',
    'wizard_widget_type'           => 'Tipo de widget',
    'wizard_widget_bubble'         => 'Bubble',
    'wizard_widget_bubble_desc'    => 'Bolha flutuante no canto',
    'wizard_widget_inline'         => 'Inline / Página',
    'wizard_widget_inline_desc'    => 'Incorporado na página',
    'wizard_widget_color'          => 'Cor do widget',

    // Wizard step: trigger keywords
    'wizard_keywords_question'     => 'Palavras-chave de disparo',
    'wizard_keywords_subtitle'     => 'Quando o contato enviar uma dessas palavras, o fluxo inicia automaticamente. Separe por vírgula.',
    'wizard_keywords_placeholder'  => 'oi, olá, bom dia, menu, preço',
    'wizard_keywords_hint'         => 'Se não definir palavras-chave, o fluxo só será ativado manualmente.',

    // Wizard step: review
    'wizard_review_question'       => 'Tudo certo? Revise antes de criar',
    'wizard_review_subtitle'       => 'Confirme as informações do seu chatbot.',
    'wizard_review_empty'          => 'Nenhum campo preenchido.',

    // Wizard review labels
    'review_channel'               => 'Canal',
    'review_name'                  => 'Nome',
    'review_description'           => 'Descrição',
    'review_template'              => 'Modelo',
    'review_bot_name'              => 'Nome do bot',
    'review_avatar'                => 'Avatar',
    'review_welcome'               => 'Boas-vindas',
    'review_widget_type'           => 'Tipo widget',
    'review_color'                 => 'Cor',
    'review_keywords'              => 'Palavras-chave',
    'review_widget_bubble'         => 'Bubble (flutuante)',
    'review_widget_inline'         => 'Inline (página)',
    'review_from_scratch'          => 'Do zero',

    // Wizard validation
    'wizard_select_channel'        => 'Selecione um canal.',
    'wizard_name_required'         => 'Dê um nome ao seu chatbot.',

    // Wizard toastr
    'toast_created'                => 'Chatbot criado com sucesso!',
    'toast_create_error'           => 'Erro ao criar chatbot.',
    'toast_connection_error'       => 'Erro de conexão. Tente novamente.',

    // ─── Results page ───────────────────────────────────────────────────────
    'results_title'                => 'Resultados',
    'results_back'                 => 'Voltar',

    // KPIs
    'results_total'                => 'Total de conversas',
    'results_finished'             => 'Finalizadas',
    'results_in_progress'          => 'Em andamento',
    'results_leads_created'        => 'Leads criados',

    // Table
    'results_table_title'          => 'Respostas',
    'results_search_placeholder'   => 'Buscar...',
    'results_filter_all'           => 'Todos status',
    'results_filter_open'          => 'Em andamento',
    'results_filter_closed'        => 'Finalizado',
    'results_csv'                  => 'CSV',
    'results_delete_selected'      => 'Excluir',
    'results_status'               => 'Status',
    'results_status_open'          => 'Em andamento',
    'results_status_closed'        => 'Finalizado',
    'results_view_conversation'    => 'Ver conversa',
    'results_no_messages'          => 'Nenhuma mensagem encontrada.',
    'results_empty'                => 'Nenhum resultado ainda. As respostas aparecem aqui quando visitantes interagem com o chatbot.',
    'results_confirm_delete'       => 'Excluir :count resultado(s)? Esta ação não pode ser desfeita.',
    'results_deleted'              => ':count resultado(s) excluído(s).',
    'results_load_error'           => 'Erro ao carregar conversa.',

    // ── Trigger type (Instagram comment) ────────────────────────────
    'trigger_type_label'           => 'Tipo de gatilho',
    'trigger_type_keyword'         => 'Palavras-chave em DM',
    'trigger_type_keyword_desc'    => 'Dispara quando o lead envia DM com palavra-chave',
    'trigger_type_comment'         => 'Comentou em publicação',
    'trigger_type_comment_desc'    => 'Dispara quando comentam em post/reel com palavra-chave',
    'trigger_post_label'           => 'Publicação alvo',
    'trigger_post_any'             => 'Qualquer publicação',
    'trigger_post_specific'        => 'Post/Reel específico',
    'trigger_reply_comment'        => 'Resposta no comentário (opcional)',
    'trigger_reply_comment_ph'     => 'Ex: Obrigado pelo interesse! Vou te mandar mais informações no privado 😊',
    'trigger_reply_comment_hint'   => 'Se preenchido, responde publicamente no comentário antes de enviar DM.',
    'trigger_load_posts'           => 'Carregar publicações',
    'trigger_load_more'            => 'Carregar mais',
    'trigger_loading'              => 'Carregando...',
    'trigger_load_error'           => 'Erro ao carregar publicações. Verifique a conexão com Instagram.',
    'trigger_keyword_hint_comment' => 'Palavras no comentário que disparam o fluxo. Ex: quero, preço, link. Deixe vazio para disparar em qualquer comentário.',
    'trigger_chatbot_hint'         => 'Precisa de fluxo com perguntas, condições e ações? Use o',
    'trigger_chatbot_link'         => 'Chatbot Builder',
    'trigger_chatbot_suffix'       => 'com o gatilho "Comentou em publicação".',
    'node_start_comment'           => 'Quando comentam em publicação',

    // ── Button types (Instagram) ────────────────────────────────────
    'btn_type_label'               => 'Tipo do botão',
    'btn_type_postback'            => 'Resposta (avança fluxo)',
    'btn_type_weburl'              => 'Link externo',
    'btn_url_label'                => 'URL',

    // ── Sidebar trigger config ──────────────────────────────────────
    'sidebar_trigger'              => 'Gatilho',
    'sidebar_trigger_keyword'      => 'Palavras-chave em DM',
    'sidebar_trigger_comment'      => 'Comentou em publicação',
    'sidebar_post_label'           => 'Publicação',
    'sidebar_post_any'             => 'Qualquer publicação',
    'sidebar_post_specific'        => 'Post/Reel específico',
    'sidebar_reply_label'          => 'Resposta no comentário',
    'sidebar_reply_ph'             => 'Ex: Vou te mandar no privado!',
    'sidebar_load_posts'           => 'Carregar posts',
    'sidebar_load_more'            => 'Mais',
    'sidebar_load_error'           => 'Erro ao carregar',
];
