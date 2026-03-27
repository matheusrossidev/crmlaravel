<?php

declare(strict_types=1);

return [

    // ── Page ──
    'page_title' => 'API / Webhooks',

    // ── API Keys card ──
    'your_api_keys'   => 'Suas API Keys',
    'new_api_key'     => 'Nova API Key',

    // ── Key item ──
    'created_at'      => 'Criada em :date',
    'last_used'       => 'Último uso: :time',
    'never_used'      => 'Nunca utilizada',
    'badge_active'    => 'Ativa',
    'badge_revoked'   => 'Revogada',
    'btn_revoke'      => 'Revogar',

    // ── Empty state ──
    'empty_title'     => 'Nenhuma API Key criada ainda.',
    'empty_cta'       => 'Clique em <strong>Nova API Key</strong> para criar.',

    // ── Endpoints card ──
    'endpoints_title' => 'Documentação dos Endpoints',
    'endpoints_intro' => 'Inclua o header <code style="background:#f0f4ff;color:#6366f1;padding:2px 6px;border-radius:4px;font-size:12px;">X-API-Key: sua_key</code> em todas as requisições. URL base:',

    // ── Endpoint descriptions ──
    'ep_post_leads'       => 'Criar novo lead — use o builder para montar o payload',
    'ep_get_lead'         => 'Buscar lead por ID',
    'ep_put_stage'        => 'Mover lead para outra etapa',
    'ep_put_won'          => 'Marcar lead como ganho',
    'ep_put_lost'         => 'Marcar lead como perdido',
    'ep_delete_lead'      => 'Deletar lead',
    'ep_get_pipelines'    => 'Listar pipelines e etapas disponíveis',

    // ── Builder section titles ──
    'builder_main_fields'    => 'Campos Principais',
    'builder_pipeline_stage' => 'Pipeline & Etapa',
    'builder_required'       => 'obrigatório',
    'builder_custom_fields'  => 'Campos Personalizados',
    'builder_campaign_utm'   => 'Campanha & UTM',
    'builder_utm_hint'       => '(atribuição automática)',
    'builder_no_pipeline'    => 'Nenhum pipeline configurado. Crie um em',
    'builder_settings_funnels' => 'Configurações → Funis',
    'builder_no_tags'        => 'Nenhuma tag configurada',
    'builder_campaign_none'  => '— Nenhuma —',
    'builder_req'            => 'req',

    // ── Builder curl preview ──
    'curl_generated' => 'cURL gerado',
    'btn_copy'       => 'Copiar',
    'btn_copied'     => 'Copiado!',

    // ── Mini builder labels ──
    'label_lead_id'       => 'Lead ID',
    'label_pipeline'      => 'Pipeline',
    'label_stage'         => 'Etapa',
    'label_pipeline_filter' => 'Pipeline',
    'label_filter_hint'   => '(filtro)',
    'label_won_stage'     => 'Etapa de Ganho',
    'label_lost_stage'    => 'Etapa de Perda',
    'label_value_optional' => 'Valor (opcional)',
    'label_reason_optional' => 'Motivo ID (opcional)',
    'label_reason_placeholder' => 'ID do motivo',

    // ── Stage dropdowns (JS) ──
    'no_won_stages'  => '— Nenhuma etapa de ganho neste pipeline —',
    'no_lost_stages' => '— Nenhuma etapa de perda neste pipeline —',
    'no_stages'      => '— Nenhuma etapa disponível —',

    // ── How-to sidebar ──
    'how_to_use'     => 'Como usar',
    'step1_title'    => '1. Gere uma API Key',
    'step1_text'     => 'Clique em <em>Nova API Key</em>, dê um nome para identificar onde será usada (ex: "Site", "Automação") e copie a key.',
    'step2_title'    => '2. Salve com segurança',
    'step2_text'     => 'A key completa é exibida <strong>apenas uma vez</strong>. Guarde em um local seguro.',
    'step3_title'    => '3. Inclua no header',
    'step3_example'  => 'X-API-Key: crm_sua_key_aqui',
    'step4_title'    => '4. URL base',

    'builder_tip_title' => 'Builder interativo',
    'builder_tip_text'  => 'Expanda <span class="endpoint-method method-post" style="font-size:10px;">POST</span> <code style="font-size:11px;">/leads</code> para usar o builder — selecione campos, pipeline e etapa e veja o cURL gerado em tempo real.',

    'isolation_notice'  => 'Todas as requisições são isoladas por conta.',

    'custom_fields_title' => 'Campos personalizados',
    'custom_fields_hint'  => 'Passe em <code style="font-size:11px;">custom_fields</code>:',

    // ── Modal: New API Key ──
    'modal_new_key_title' => 'Nova API Key',
    'modal_key_name_label' => 'Nome da key',
    'modal_key_name_placeholder' => 'Ex: Site, Landing Page, Automação...',
    'modal_cancel'        => 'Cancelar',
    'modal_create'        => 'Criar',
    'modal_creating'      => 'Criando...',

    // ── Modal: Key reveal ──
    'modal_copy_warning'  => 'Copie agora! Esta key <strong>não será exibida novamente</strong>.',
    'modal_done_copied'   => 'Feito, já copiei',

    // ── Toastr messages ──
    'toast_name_required'     => 'Informe um nome para a API Key.',
    'toast_create_error'      => 'Erro ao criar API Key.',
    'toast_connection_error'  => 'Erro de conexão.',
    'toast_revoked_success'   => 'API Key revogada.',
    'toast_revoke_error'      => 'Erro ao revogar.',

    // ── Confirm dialog ──
    'confirm_revoke_title'   => 'Revogar API Key',
    'confirm_revoke_message' => 'Sistemas que utilizam esta chave perderão acesso imediatamente.',
    'confirm_revoke_btn'     => 'Revogar',

];
