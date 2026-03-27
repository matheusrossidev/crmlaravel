<?php

declare(strict_types=1);

return [

    // ── Page ────────────────────────────────────────────────────────
    'page_title'    => 'Automações de Instagram',
    'page_subtitle' => 'Responda comentários e envie DMs automaticamente com base em palavras-chave.',

    // ── Banner ──────────────────────────────────────────────────────
    'banner_not_connected'      => 'Instagram não está conectado. Para usar Automações,',
    'banner_go_to_integrations' => 'vá em Integrações',
    'banner_connect_account'    => 'e conecte sua conta.',

    // ── Card header ─────────────────────────────────────────────────
    'card_title'      => 'Automações de Comentários',
    'btn_new'         => 'Nova Automação',

    // ── Item labels ─────────────────────────────────────────────────
    'specific_post'   => 'Publicação específica',
    'all_posts'       => 'Todos os posts',
    'match_all'       => 'Todas as palavras',
    'match_any'       => 'Qualquer palavra',
    'keywords_count'  => ':count palavra(s)-chave',
    'comments_replied' => ':count comentário(s) respondido(s)',
    'dms_sent'        => ':count DM(s) enviada(s)',

    // ── Toggle / actions ────────────────────────────────────────────
    'toggle_active'   => 'Ativa',
    'toggle_inactive' => 'Inativa',
    'btn_edit'        => 'Editar',
    'btn_delete'      => 'Excluir',

    // ── Empty state ─────────────────────────────────────────────────
    'empty_title'     => 'Nenhuma automação criada ainda.',
    'empty_hint'      => 'Clique em <strong>Nova Automação</strong> para começar.',

    // ── Drawer ──────────────────────────────────────────────────────
    'drawer_title_new'  => 'Nova Automação',
    'drawer_title_edit' => 'Editar Automação',

    // Name
    'label_name'             => 'Nome',
    'label_name_optional'    => '(opcional)',
    'placeholder_name'       => 'Ex: Responder sobre preços',

    // Post scope
    'label_target_post'      => 'Publicação alvo',
    'scope_all_posts'        => 'Todos os posts',
    'scope_specific_post'    => 'Publicação específica',

    // Post picker
    'btn_load_more'          => 'Carregar mais',
    'error_load_posts'       => 'Erro ao carregar publicações.',

    // Keywords
    'label_keywords'         => 'Palavras-chave',
    'keywords_hint'          => '(Enter ou vírgula para adicionar)',
    'placeholder_keyword'    => 'Digite uma palavra...',

    // Match type
    'label_match_type'       => 'Correspondência',
    'match_any_or'           => 'Qualquer palavra (OU)',
    'match_all_and'          => 'Todas as palavras (E)',

    // Reply comment
    'label_reply_comment'          => 'Responder ao comentário',
    'label_reply_comment_optional' => '(opcional)',
    'placeholder_reply_comment'    => 'Resposta pública postada no comentário...',

    // DM builder
    'label_send_dm'          => 'Enviar DM',
    'label_send_dm_optional' => '(opcional — sequência de mensagens)',
    'dm_block_text'          => 'Texto',
    'dm_block_image'         => 'Imagem',
    'dm_placeholder_url'     => 'https://public.com/imagem.jpg',
    'dm_links_hint'          => 'Links ficam clicáveis automaticamente',
    'dm_placeholder_message' => 'Escreva sua mensagem...',
    'dm_preview_label'       => 'Prévia Instagram DM',
    'dm_preview_placeholder' => 'Prévia aparecerá aqui...',

    // Quick Reply Buttons
    'dm_buttons_label'       => 'Quick Reply Buttons',
    'dm_buttons_optional'    => '(opcional, max. 13)',
    'dm_btn_placeholder'     => 'Texto do botao (max. 20 chars)',
    'dm_btn_add'             => '+ Botao',

    // Drawer footer
    'btn_cancel'             => 'Cancelar',
    'btn_save'               => 'Salvar',
    'btn_saving'             => 'Salvando…',

    // ── Confirm delete dialog ───────────────────────────────────────
    'confirm_delete_title'   => 'Excluir Automação',
    'confirm_delete_message' => 'Tem certeza que deseja excluir esta automação? Esta ação não pode ser desfeita.',
    'btn_confirm_delete'     => 'Excluir',
    'btn_deleting'           => 'Excluindo…',

    // ── Alerts / Toastr ─────────────────────────────────────────────
    'alert_error_delete'         => 'Erro ao excluir.',
    'alert_network_error'        => 'Erro de rede. Tente novamente.',
    'alert_keyword_required'     => 'Adicione pelo menos uma palavra-chave.',
    'alert_action_required'      => 'Defina pelo menos uma ação: resposta ao comentário ou DM.',
    'alert_error_save'           => 'Erro ao salvar.',
    'alert_error_save_detail'    => 'Erro ao salvar: :message',
    'toastr_max_buttons'         => 'Maximo de 13 botoes por mensagem.',
];
