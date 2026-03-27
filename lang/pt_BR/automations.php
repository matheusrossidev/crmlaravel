<?php

return [
    // ── List page ───────────────────────────────────────────────────────
    'title'                     => 'Automações',
    'subtitle'                  => 'Crie regras para automatizar ações quando eventos ocorrerem no CRM.',
    'new_automation'            => 'Nova Automação',
    'empty_icon'                => 'Nenhuma automação criada ainda.',
    'empty_hint'                => 'Clique em <strong>Nova Automação</strong> para começar.',

    // ── Table columns ───────────────────────────────────────────────────
    'col_name'                  => 'Nome',
    'col_trigger'               => 'Gatilho',
    'col_actions'               => 'Ações',
    'col_runs'                  => 'Execuções',
    'col_active'                => 'Ativo',

    // ── Table actions ───────────────────────────────────────────────────
    'btn_edit'                  => 'Editar',
    'btn_delete'                => 'Excluir',
    'confirm_delete'            => 'Excluir esta automação?',

    // ── Toggle / delete toasts ──────────────────────────────────────────
    'toast_activated'           => 'Ativada.',
    'toast_deactivated'         => 'Desativada.',
    'toast_error'               => 'Erro.',
    'toast_deleted'             => 'Excluída.',
    'toast_delete_error'        => 'Erro ao excluir.',

    // ── Action labels (list page badges) ────────────────────────────────
    'action_add_tag_lead'       => 'Tag no lead',
    'action_remove_tag_lead'    => 'Remover tag',
    'action_add_tag_conversation' => 'Tag na conversa',
    'action_move_to_stage'      => 'Mover etapa',
    'action_set_lead_source'    => 'Definir origem',
    'action_assign_to_user'     => 'Atribuir usuário',
    'action_add_note'           => 'Adicionar nota',
    'action_assign_ai_agent'    => 'Agente IA',
    'action_assign_chatbot_flow' => 'Chatbot',
    'action_close_conversation' => 'Fechar conversa',
    'action_send_whatsapp_message' => 'Enviar msg WA',
    'action_create_task'        => 'Criar tarefa',

    // ── Trigger labels (badge + sidebar + canvas) ───────────────────────
    'trigger_message_received'      => 'Mensagem recebida',
    'trigger_conversation_created'  => 'Nova conversa',
    'trigger_lead_created'          => 'Lead criado',
    'trigger_lead_stage_changed'    => 'Lead movido de etapa',
    'trigger_lead_won'              => 'Lead ganho',
    'trigger_lead_lost'             => 'Lead perdido',
    'trigger_date_field'            => 'Data / Aniversário',
    'trigger_recurring'             => 'Recorrente',
    'trigger_recurring_full'        => 'Recorrente (Semanal/Mensal)',

    // ── Form page: header ───────────────────────────────────────────────
    'name_placeholder'          => 'Nome da automação...',
    'status_active'             => 'Ativa',
    'status_inactive'           => 'Inativa',
    'btn_cancel'                => 'Cancelar',
    'btn_save'                  => 'Salvar automação',

    // ── Form page: sidebar sections ─────────────────────────────────────
    'sidebar_trigger'           => 'Gatilho',
    'sidebar_conditions'        => 'Condições',
    'sidebar_actions'           => 'Ações',

    // ── Sidebar: trigger items ──────────────────────────────────────────
    'sidebar_message_received'      => 'Mensagem recebida',
    'sidebar_conversation_created'  => 'Nova conversa',
    'sidebar_lead_created'          => 'Lead criado',
    'sidebar_lead_stage_changed'    => 'Lead movido de etapa',
    'sidebar_lead_won'              => 'Lead ganho',
    'sidebar_lead_lost'             => 'Lead perdido',
    'sidebar_date_field'            => 'Data / Aniversário',
    'sidebar_recurring'             => 'Recorrente',

    // ── Sidebar: condition items ────────────────────────────────────────
    'sidebar_cond_message_body'     => 'Corpo da mensagem',
    'sidebar_cond_lead_source'      => 'Origem do lead',
    'sidebar_cond_lead_tag'         => 'Tag do lead',
    'sidebar_cond_conversation_tag' => 'Tag da conversa',

    // ── Sidebar: action items ───────────────────────────────────────────
    'sidebar_act_add_tag_lead'          => 'Adicionar tag ao lead',
    'sidebar_act_remove_tag_lead'       => 'Remover tag do lead',
    'sidebar_act_add_tag_conversation'  => 'Tag na conversa',
    'sidebar_act_move_to_stage'         => 'Mover para etapa',
    'sidebar_act_set_lead_source'       => 'Definir origem do lead',
    'sidebar_act_assign_to_user'        => 'Atribuir a usuário',
    'sidebar_act_add_note'              => 'Adicionar nota',
    'sidebar_act_assign_ai_agent'       => 'Atribuir agente de IA',
    'sidebar_act_assign_chatbot_flow'   => 'Atribuir chatbot',
    'sidebar_act_transfer_to_department' => 'Transferir p/ departamento',
    'sidebar_act_close_conversation'    => 'Fechar conversa',
    'sidebar_act_send_whatsapp_message' => 'Enviar msg WhatsApp',
    'sidebar_act_schedule_whatsapp_message' => 'Agendar msg WhatsApp',
    'sidebar_act_assign_campaign'       => 'Atribuir campanha',
    'sidebar_act_set_utm_params'        => 'Definir parâmetros UTM',
    'sidebar_act_create_task'           => 'Criar tarefa',

    // ── Canvas: placeholders & group labels ─────────────────────────────
    'trigger_placeholder'       => 'Selecione um <strong>Gatilho</strong> no painel esquerdo para começar',
    'conditions_label'          => 'SE as condições forem atendidas...',
    'actions_label'             => 'ENTÃO executar...',
    'add_action_btn'            => 'Adicionar ação',

    // ── Node type labels ────────────────────────────────────────────────
    'node_type_trigger'         => 'Gatilho',
    'node_type_condition'       => 'Condição',
    'node_type_action'          => 'Ação',

    // ── Trigger config: channel ─────────────────────────────────────────
    'label_channel'             => 'Canal',
    'channel_both'              => 'WhatsApp e Instagram',
    'channel_whatsapp'          => 'Somente WhatsApp',
    'channel_instagram'         => 'Somente Instagram',

    // ── Trigger config: pipeline / stage ────────────────────────────────
    'label_pipeline'            => 'Funil',
    'label_pipeline_optional'   => 'opcional',
    'any_pipeline'              => 'Qualquer funil',
    'label_target_stage'        => 'Etapa destino',
    'any_stage'                 => 'Qualquer etapa',

    // ── Trigger config: source ──────────────────────────────────────────
    'label_source'              => 'Origem',
    'any_source'                => 'Qualquer origem',

    // ── Trigger config: date field ──────────────────────────────────────
    'label_date_field'          => 'Campo de data',
    'date_field_birthday'       => 'Aniversário (campo nativo)',
    'date_field_native_group'   => 'Campo nativo',
    'date_field_custom_group'   => 'Campos personalizados',
    'date_field_custom_prefix'  => 'Campo:',
    'label_days_before'         => 'Dias de antecedência',
    'days_before_hint'          => '0 = no próprio dia',
    'label_repeat_yearly'       => 'Repetir anualmente (ex: aniversários)',

    // ── Trigger config: recurring ───────────────────────────────────────
    'label_recurrence_type'     => 'Tipo de recorrência',
    'recurrence_weekly'         => 'Semanal',
    'recurrence_monthly'        => 'Mensal',
    'label_month_days'          => 'Dias do mês',
    'month_days_hint'           => 'separe por vírgula: 10, 20',
    'month_days_placeholder'    => '10, 20',
    'label_send_time'           => 'Horário de envio',
    'label_filter_leads'        => 'Filtrar leads por',
    'filter_all'                => 'Todos os leads',
    'filter_tag'                => 'Tag específica',
    'filter_stage'              => 'Etapa do funil',
    'filter_tag_placeholder'    => 'Nome da tag (ex: Pais)',
    'label_daily_limit'         => 'Limite diário',
    'label_delay_between'       => 'Delay entre envios (s)',
    'recurring_safety_note'     => 'Só envia para leads com conversa WhatsApp existente. Delay entre envios para evitar bloqueio.',
    'no_trigger_config'         => 'Nenhuma configuração necessária para este gatilho.',

    // ── Weekday abbreviations ───────────────────────────────────────────
    'day_sun'                   => 'Dom',
    'day_mon'                   => 'Seg',
    'day_tue'                   => 'Ter',
    'day_wed'                   => 'Qua',
    'day_thu'                   => 'Qui',
    'day_fri'                   => 'Sex',
    'day_sat'                   => 'Sáb',

    // ── Condition config: operators ─────────────────────────────────────
    'operator_contains'         => 'contém',
    'operator_not_contains'     => 'não contém',
    'operator_equals'           => 'é igual a',
    'operator_starts_with'      => 'começa com',
    'operator_is'               => 'é',
    'operator_is_not'           => 'não é',

    // ── Condition config: labels ────────────────────────────────────────
    'label_operator'            => 'Operador',
    'label_value'               => 'Valor',
    'placeholder_keyword'       => 'Palavra-chave...',
    'label_origin'              => 'Origem',
    'placeholder_select'        => 'Selecione...',
    'label_tag'                 => 'Tag',

    // ── Action config: labels ───────────────────────────────────────────
    'label_tags'                => 'Tags',
    'label_stage'               => 'Etapa',
    'placeholder_pipeline'      => 'Funil...',
    'placeholder_stage'         => 'Etapa...',
    'label_user'                => 'Usuário',
    'label_note_text'           => 'Texto da nota',
    'placeholder_note'          => 'Digite a nota...',
    'label_ai_agent'            => 'Agente de IA',
    'no_ai_agents'              => 'Nenhum agente de IA ativo (WhatsApp).',
    'label_flow'                => 'Fluxo',
    'no_chatbot_flows'          => 'Nenhum fluxo de chatbot ativo.',
    'label_department'          => 'Departamento',
    'no_departments'            => 'Nenhum departamento ativo.',
    'close_conversation_info'   => 'A conversa vinculada ao lead será fechada automaticamente.',
    'label_campaign'            => 'Campanha',
    'no_campaigns'              => 'Nenhuma campanha cadastrada.',

    // ── Action config: UTM ──────────────────────────────────────────────
    'utm_source'                => 'UTM Source',
    'utm_medium'                => 'UTM Medium',
    'utm_campaign'              => 'UTM Campaign',
    'utm_term'                  => 'UTM Term',
    'utm_content'               => 'UTM Content',
    'utm_optional'              => 'opcional',
    'utm_placeholder_source'    => 'ex: google',
    'utm_placeholder_medium'    => 'ex: cpc',
    'utm_placeholder_campaign'  => 'ex: black-friday',
    'utm_placeholder_term'      => 'ex: crm+software',
    'utm_placeholder_content'   => 'ex: banner-topo',
    'utm_blank_hint'            => 'Deixe em branco os campos que não deseja alterar.',

    // ── Action config: WhatsApp message ─────────────────────────────────
    'label_message'             => 'Mensagem',
    'placeholder_message'       => 'Digite a mensagem...',
    'no_whatsapp_instance'      => 'Nenhuma instância WhatsApp conectada.',

    // ── Action config: Schedule message ─────────────────────────────────
    'label_send_after'          => 'Enviar após',
    'label_unit'                => 'Unidade',
    'unit_hours'                => 'Horas',
    'unit_days'                 => 'Dias',

    // ── Action config: Create task ──────────────────────────────────────
    'label_subject'             => 'Assunto',
    'placeholder_subject'       => 'Ligar para @{{contact_name}}',
    'label_description'         => 'Descrição',
    'placeholder_description'   => 'Detalhes da tarefa...',
    'label_task_type'           => 'Tipo',
    'task_type_call'            => 'Ligar',
    'task_type_email'           => 'Email',
    'task_type_task'            => 'Tarefa',
    'task_type_visit'           => 'Visita',
    'task_type_whatsapp'        => 'WhatsApp',
    'task_type_meeting'         => 'Reunião',
    'label_priority'            => 'Prioridade',
    'priority_low'              => 'Baixa',
    'priority_medium'           => 'Média',
    'priority_high'             => 'Alta',
    'label_due_days'            => 'Prazo (dias)',
    'label_due_time'            => 'Horário',
    'label_assign_to'           => 'Atribuir a',
    'assign_auto'               => 'Automático (responsável do lead)',

    // ── Tag widget ──────────────────────────────────────────────────────
    'tag_placeholder'           => 'Digite ou selecione...',
    'tag_add_new'               => 'Adicionar',
    'tag_no_suggestions'        => 'Sem sugestões',

    // ── Save validation toasts ──────────────────────────────────────────
    'validation_name_required'  => 'Informe o nome da automação.',
    'validation_trigger_required' => 'Selecione um gatilho.',
    'validation_select_tag'     => 'Selecione ao menos uma tag.',
    'validation_select_stage'   => 'Selecione a etapa destino.',
    'validation_select_source'  => 'Selecione a origem.',
    'validation_select_user'    => 'Selecione o usuário.',
    'validation_note_required'  => 'Informe o texto da nota.',
    'validation_select_ai_agent' => 'Selecione o agente de IA.',
    'validation_select_flow'    => 'Selecione o fluxo.',
    'validation_select_department' => 'Selecione o departamento.',
    'validation_message_required' => 'Informe a mensagem.',
    'validation_schedule_message_required' => 'Informe a mensagem para agendar.',
    'validation_delay_min'      => 'O delay deve ser ao menos 1.',
    'validation_select_campaign' => 'Selecione a campanha.',
    'validation_utm_required'   => 'Preencha ao menos um campo UTM.',
    'validation_subject_required' => 'Informe o assunto da tarefa.',
    'validation_action_required' => 'Adicione ao menos uma ação.',
    'validation_recurring_days' => 'Selecione pelo menos um dia para a recorrência.',

    // ── Save toasts ─────────────────────────────────────────────────────
    'toast_created'             => 'Automação criada.',
    'toast_updated'             => 'Automação atualizada.',
    'toast_save_error'          => 'Erro ao salvar.',
    'toast_comm_error'          => 'Erro de comunicação.',

    // ── Misc ────────────────────────────────────────────────────────────
    'toast_select_action_hint'  => 'Selecione uma ação no painel esquerdo.',
    'back'                      => 'Voltar',
];
