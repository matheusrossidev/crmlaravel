<?php

return [
    'title'       => 'Lead Scoring',
    'subtitle'    => 'Configure regras de pontuação para classificar seus leads automaticamente',
    'new_rule'    => 'Nova Regra',
    'edit_rule'   => 'Editar Regra',
    'no_rules'    => 'Nenhuma regra de scoring configurada.',
    'no_rules_sub' => 'Crie regras para pontuar leads automaticamente com base em engajamento, pipeline e perfil.',

    // Table headers
    'col_name'      => 'Nome',
    'col_category'  => 'Categoria',
    'col_event'     => 'Evento',
    'col_points'    => 'Pontos',
    'col_cooldown'  => 'Cooldown',
    'col_status'    => 'Status',
    'col_actions'   => 'Ações',

    // Form
    'field_name'           => 'Nome da regra',
    'field_name_placeholder' => 'Ex: Lead respondeu mensagem',
    'field_category'       => 'Categoria',
    'field_event'          => 'Evento disparador',
    'field_points'         => 'Pontos',
    'field_points_help'    => 'Valores positivos somam, negativos subtraem.',
    'field_cooldown'       => 'Cooldown (horas)',
    'field_cooldown_help'  => 'Tempo mínimo entre disparos da mesma regra para o mesmo lead. 0 = sem limite.',
    'field_active'         => 'Ativa',

    // Categories
    'cat_engagement' => 'Engajamento',
    'cat_pipeline'   => 'Pipeline',
    'cat_profile'    => 'Perfil',

    // Event types
    'evt_message_received'   => 'Mensagem recebida',
    'evt_message_sent_media' => 'Mídia enviada pelo lead',
    'evt_fast_reply'         => 'Resposta rápida (< 5 min)',
    'evt_stage_advanced'     => 'Avançou de etapa',
    'evt_stage_regressed'    => 'Retrocedeu de etapa',
    'evt_lead_won'           => 'Venda fechada',
    'evt_lead_lost'          => 'Venda perdida',
    'evt_profile_complete'   => 'Perfil completo (email + empresa)',
    'evt_inactive_3d'        => 'Inativo há 3 dias',
    'evt_inactive_7d'        => 'Parado na etapa há 7 dias',

    // Actions
    'btn_save'   => 'Salvar',
    'btn_cancel' => 'Cancelar',
    'btn_delete' => 'Excluir',

    // Toasts
    'toast_created' => 'Regra criada com sucesso!',
    'toast_updated' => 'Regra atualizada com sucesso!',
    'toast_deleted' => 'Regra excluída.',
    'toast_error'   => 'Erro ao salvar regra.',

    // Confirm
    'confirm_delete_title' => 'Excluir regra?',
    'confirm_delete_msg'   => 'Essa ação não pode ser desfeita. Os logs de score existentes serão mantidos.',
    'confirm_delete_btn'   => 'Sim, excluir',

    // Score display
    'score_label'    => 'Score',
    'score_high'     => 'Quente',
    'score_medium'   => 'Morno',
    'score_low'      => 'Frio',
    'breakdown'      => 'Detalhamento',

    // Hours
    'hours_none' => 'Sem limite',
    'hours_unit' => ':count h',
];
