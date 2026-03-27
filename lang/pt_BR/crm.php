<?php

return [
    // Page
    'title' => 'CRM',

    // Header buttons
    'filters'         => 'Filtros',
    'export_leads'    => 'Exportar leads',
    'import_leads'    => 'Importar leads',
    'new_lead'        => 'Novo Lead',
    'create_pipeline' => 'Criar funil',

    // Filters
    'all_sources'     => 'Todas as origens',
    'all_tags'        => 'Todas as tags',
    'date_from'       => 'Data de',
    'date_to'         => 'Data até',
    'responsible'     => 'Responsável',
    'ai_agent'        => 'Agente IA',
    'apply'           => 'Aplicar',
    'clear'           => 'Limpar',

    // Card actions
    'call'            => 'Ligar',
    'open_conversation' => 'Abrir conversa',
    'send_email'      => 'Enviar email',

    // Task bar (relative dates)
    'days_ago'        => 'd atrás',
    'today'           => 'Hoje',
    'tomorrow'        => 'Amanhã',

    // Board
    'drag_here'       => 'Arraste leads aqui',
    'add_lead'        => 'Adicionar lead',
    'no_stages'       => 'Nenhuma etapa configurada neste pipeline.',

    // Won modal
    'won_title'       => 'Lead Ganho!',
    'won_desc'        => 'Informe o valor do negócio (opcional).',
    'won_placeholder' => 'Valor (ex: 1500.00)',
    'skip'            => 'Pular',
    'confirm'         => 'Confirmar',

    // Lost modal
    'lost_title'      => 'Lead Perdido',
    'lost_desc'       => 'Selecione o motivo da perda (opcional).',
    'no_reason'       => 'Sem motivo',

    // Empty state
    'no_pipeline'           => 'Nenhum funil configurado',
    'no_pipeline_desc'      => 'Crie seu primeiro funil de vendas para começar a organizar seus leads em etapas e acompanhar o progresso do seu negócio.',
    'create_first_pipeline' => 'Criar meu primeiro funil',

    // Pipeline drawer
    'create_new_pipeline' => 'Criar novo funil',
    'pipeline_name'       => 'Nome do funil',
    'pipeline_name_ph'    => 'Ex: Vendas 2025',
    'color'               => 'Cor',
    'cancel'              => 'Cancelar',
    'create_pipeline_btn' => 'Criar funil',

    // Import modal
    'import_title'        => 'Importar Leads',
    'template_title'      => 'Planilha modelo',
    'template_desc'       => 'Inclui as etapas do funil atual como referência',
    'download'            => 'Baixar',
    'select_file'         => 'Selecionar arquivo',
    'file_formats'        => 'Formatos: .xlsx, .xls, .csv — máximo 5 MB',
    'preview'             => 'Pré-visualizar',
    'preview_title'       => 'Pré-visualização',
    'back'                => 'Voltar',
    'confirm_import'      => 'Confirmar importação',
    'col_name'            => 'Nome',
    'col_phone'           => 'Telefone',
    'col_email'           => 'E-mail',
    'col_value'           => 'Valor',
    'col_stage'           => 'Etapa',
    'col_tags'            => 'Tags',
    'col_source'          => 'Origem',
    'col_created'         => 'Criado em',

    // JS messages
    'lead_moved'          => 'Lead movido!',
    'error_move_lead'     => 'Erro ao mover lead. Recarregue a página.',
    'new_lead_toast'      => 'Novo lead: :name',
    'error_create_pipeline' => 'Erro ao criar o funil. Tente novamente.',
    'select_file_first'   => 'Selecione um arquivo antes de pré-visualizar.',
    'no_pipeline_selected' => 'Nenhum funil selecionado.',
    'analyzing'           => 'Analisando...',
    'error_analyze_file'  => 'Erro ao analisar o arquivo. Verifique o formato e tente novamente.',
    'token_missing'       => 'Token ausente. Volte e tente novamente.',
    'importing'           => 'Importando...',
    'import_success'      => ':count lead(s) importado(s) com sucesso!',
    'import_skipped'      => ':count ignorado(s).',
    'error_import'        => 'Erro ao importar. Tente novamente.',
    'creating'            => 'Criando…',
    'pipeline_prefix'     => 'Funil: :name',
    'no_name_skip'        => '(sem nome — ignorado)',
    'stage_not_found'     => 'Etapa não encontrada — será usada a etapa inicial',

    // Source labels
    'source_facebook'     => 'Facebook Ads',
    'source_google'       => 'Google Ads',
    'source_instagram'    => 'Instagram',
    'source_whatsapp'     => 'WhatsApp',
    'source_site'         => 'Site',
    'source_indicacao'    => 'Indicação',
    'source_api'          => 'API',
    'source_manual'       => 'Manual',
    'source_outro'        => 'Outro',
];
