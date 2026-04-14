<?php

return [
    // Menu
    'menu_title' => 'Templates WhatsApp',

    // Index
    'title'       => 'Templates WhatsApp',
    'subtitle'    => 'Mensagens pré-aprovadas pela Meta usadas pra iniciar conversa fora da janela de 24h.',
    'create'      => 'Criar template',
    'sync'        => 'Sincronizar com Meta',
    'sync_running'=> 'Sincronizando...',
    'empty_title' => 'Nenhum template cadastrado',
    'empty_body'  => 'Crie seu primeiro template pra poder enviar mensagens fora da janela de 24h.',

    // Tabela
    'col_name'       => 'Nome',
    'col_instance'   => 'Instância',
    'col_language'   => 'Idioma',
    'col_category'   => 'Categoria',
    'col_status'     => 'Status',
    'col_last_sync'  => 'Última sync',
    'col_actions'    => 'Ações',

    // Status
    'status_approved' => 'Aprovado',
    'status_pending'  => 'Em análise',
    'status_rejected' => 'Rejeitado',
    'status_paused'   => 'Pausado',
    'status_disabled' => 'Desativado',
    'status_in_appeal'=> 'Em recurso',

    // Categorias
    'cat_utility'        => 'Utilidade',
    'cat_marketing'      => 'Marketing',
    'cat_authentication' => 'Autenticação',
    'cat_utility_desc'        => 'Confirmações, lembretes, atualizações de pedido',
    'cat_marketing_desc'      => 'Ofertas, promoções e novidades (cobrança mais alta pela Meta)',
    'cat_authentication_desc' => 'Códigos OTP e verificação em duas etapas',

    // Form labels
    'form_name'        => 'Nome do template',
    'form_name_hint'   => 'snake_case, sem acentos. Ex: lembrete_consulta, codigo_otp',
    'form_language'    => 'Idioma',
    'form_category'    => 'Categoria',
    'form_instance'    => 'Instância (WABA)',
    'form_header'      => 'Cabeçalho (opcional)',
    'form_header_none' => 'Sem cabeçalho',
    'form_header_text' => 'Texto',
    'form_header_image'=> 'Imagem',
    'form_header_video'=> 'Vídeo',
    'form_header_doc'  => 'Documento',
    'form_header_hint_media' => 'Você fornece a mídia ao enviar (upload no chat).',
    'form_body'        => 'Corpo da mensagem',
    'form_body_hint'   => 'Use {{1}}, {{2}} pra variáveis. Máx 1024 caracteres.',
    'form_samples'     => 'Exemplos das variáveis',
    'form_samples_hint'=> 'A Meta exige exemplos reais pra revisar seu template.',
    'form_footer'      => 'Rodapé (opcional)',
    'form_footer_hint' => 'Máx 60 caracteres. Sem variáveis.',
    'form_buttons'     => 'Botões (opcional)',
    'form_add_button'  => '+ Adicionar botão',
    'form_btn_type'    => 'Tipo',
    'form_btn_quick'   => 'Resposta rápida',
    'form_btn_url'     => 'URL',
    'form_btn_phone'   => 'Telefone',
    'form_btn_copy'    => 'Copiar código',
    'form_submit'      => 'Enviar pra análise da Meta',

    // Preview
    'preview_title' => 'Pré-visualização',
    'preview_hint'  => 'Como vai aparecer no WhatsApp do cliente.',

    // Show
    'show_rejected_reason' => 'Motivo da rejeição',
    'show_quality'         => 'Qualidade',
    'show_meta_id'         => 'ID Meta',
    'show_last_sync'       => 'Última sincronização',
    'show_delete'          => 'Excluir template',
    'show_delete_confirm'  => 'Excluir esse template? Ele será removido também da Meta.',

    // Modal de envio no chat
    'modal_title'        => 'Enviar template',
    'modal_select'       => 'Escolha um template aprovado',
    'modal_search'       => 'Buscar por nome...',
    'modal_variables'    => 'Preencher variáveis',
    'modal_header_media' => 'Anexar mídia do cabeçalho',
    'modal_send'         => 'Enviar',
    'modal_no_approved'  => 'Nenhum template aprovado nessa instância. Crie ou aguarde aprovação da Meta.',

    // Window 24h
    'window_closed_notice' => 'A janela de 24h dessa conversa foi fechada. Use um template aprovado pra retomar.',
    'window_closed_cta'    => 'Enviar template',
    'compose_template_btn' => 'Template',

    // Toasts
    'toast_created'      => 'Template enviado pra análise da Meta. Aprovação leva 24 a 72h.',
    'toast_deleted'      => 'Template excluído.',
    'toast_sent'         => 'Template enviado.',
    'toast_sync_done'    => 'Sincronização concluída.',
    'toast_sync_error'   => 'Erro na sincronização: :msg',

    // Erros de validação
    'err_name_invalid'           => 'Nome deve ser snake_case com no máximo 64 caracteres.',
    'err_variables_not_sequential'=> 'As variáveis precisam ser sequenciais começando em {{1}}.',
    'err_sample_required'        => 'Preencha exemplos pra todas as variáveis.',
    'err_not_approved'           => 'Só templates aprovados podem ser enviados.',
];
