<?php

return [
    'title'       => 'Sequências de Nutrição',
    'subtitle'    => 'Crie cadências automáticas de mensagens para nutrir seus leads ao longo do tempo',
    'new'         => 'Nova Sequência',
    'edit'        => 'Editar Sequência',
    'no_sequences' => 'Nenhuma sequência criada.',
    'no_sequences_sub' => 'Crie sequências para enviar mensagens automáticas ao longo do tempo.',

    // Table
    'col_name'      => 'Nome',
    'col_steps'     => 'Steps',
    'col_enrolled'  => 'Inscritos',
    'col_completed' => 'Completados',
    'col_active'    => 'Ativos',
    'col_status'    => 'Status',

    // Form
    'field_name'        => 'Nome da sequência',
    'field_name_ph'     => 'Ex: Nutrição Pós-Contato',
    'field_desc'        => 'Descrição',
    'field_desc_ph'     => 'Breve descrição do objetivo',
    'field_exit_reply'  => 'Parar se lead responder',
    'field_exit_stage'  => 'Parar se lead mudar de etapa',
    'section_settings'  => 'Configurações',
    'section_steps'     => 'Steps da Sequência',

    // Steps
    'step_type'          => 'Tipo',
    'step_delay'         => 'Aguardar',
    'step_delay_help'    => 'Tempo de espera após o step anterior',
    'step_message'       => 'Mensagem',
    'step_wait_reply'    => 'Aguardar Resposta',
    'step_condition'     => 'Condição',
    'step_action'        => 'Ação',
    'step_body'          => 'Texto da mensagem',
    'step_body_ph'       => 'Olá {{nome}}, tudo bem? ...',
    'step_timeout'       => 'Timeout (minutos)',
    'step_add'           => 'Adicionar Step',
    'step_remove'        => 'Remover',

    // Delay units
    'minutes' => 'minutos',
    'hours'   => 'horas',
    'days'    => 'dias',

    // Actions
    'btn_save'   => 'Salvar',
    'btn_cancel' => 'Cancelar',

    // Toasts
    'toast_created' => 'Sequência criada!',
    'toast_updated' => 'Sequência atualizada!',
    'toast_deleted' => 'Sequência excluída.',
    'toast_error'   => 'Erro ao salvar.',
    'toast_toggled_on'  => 'Sequência ativada!',
    'toast_toggled_off' => 'Sequência desativada.',

    // Confirm
    'confirm_delete_title' => 'Excluir sequência?',
    'confirm_delete_msg'   => 'Todos os leads inscritos serão removidos. Essa ação não pode ser desfeita.',
    'confirm_delete_btn'   => 'Sim, excluir',

    // Variables
    'variables_help' => 'Variáveis: {{nome}}, {{empresa}}, {{email}}, {{phone}}, {{etapa}}, {{score}}',

    // Lead badge
    'badge_active'    => 'Em sequência',
    'badge_step'      => 'Step :current/:total',

    // Nav
    'nav_title' => 'Sequências',
];
