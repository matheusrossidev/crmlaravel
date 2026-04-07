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

    // ===== Fase 1: Filtros estruturais e limites globais =====
    'section_filters'      => 'Filtros e limites avançados',
    'pipeline_filter'      => 'Filtro por funil',
    'pipeline_filter_help' => 'Quando preenchido, a regra só dispara para leads desse funil. Vazio = qualquer funil.',
    'any_pipeline'         => 'Qualquer funil',
    'stage_filter'         => 'Filtro por etapa',
    'stage_filter_help'    => 'Restringe a regra a uma etapa específica do funil escolhido acima.',
    'any_stage'            => 'Qualquer etapa',
    'valid_from'           => 'Válida a partir de',
    'valid_until'          => 'Válida até',
    'max_triggers'         => 'Limite por lead (vida toda)',
    'max_triggers_help'    => 'Quantas vezes essa regra pode disparar para o mesmo lead. Vazio = sem limite.',
    'no_limit'             => 'Sem limite',

    // Limites globais de score (Fix 7)
    'global_limits'      => 'Limites globais de score',
    'global_limits_help' => 'Aplica em todos os leads do tenant. O score nunca cai abaixo do mínimo nem ultrapassa o máximo.',
    'score_min_label'    => 'Score mínimo',
    'score_max_label'    => 'Score máximo',
    'no_max'             => 'Sem teto',
    'save_limits'        => 'Salvar limites',
    'limits_saved'       => 'Limites salvos!',
];
