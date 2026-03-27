<?php

return [
    // ── List page ───────────────────────────────────────────────────────
    'title'                     => 'Automations',
    'subtitle'                  => 'Create rules to automate actions when events occur in the CRM.',
    'new_automation'            => 'New Automation',
    'empty_icon'                => 'No automations created yet.',
    'empty_hint'                => 'Click <strong>New Automation</strong> to get started.',

    // ── Table columns ───────────────────────────────────────────────────
    'col_name'                  => 'Name',
    'col_trigger'               => 'Trigger',
    'col_actions'               => 'Actions',
    'col_runs'                  => 'Runs',
    'col_active'                => 'Active',

    // ── Table actions ───────────────────────────────────────────────────
    'btn_edit'                  => 'Edit',
    'btn_delete'                => 'Delete',
    'confirm_delete'            => 'Delete this automation?',

    // ── Toggle / delete toasts ──────────────────────────────────────────
    'toast_activated'           => 'Activated.',
    'toast_deactivated'         => 'Deactivated.',
    'toast_error'               => 'Error.',
    'toast_deleted'             => 'Deleted.',
    'toast_delete_error'        => 'Failed to delete.',

    // ── Action labels (list page badges) ────────────────────────────────
    'action_add_tag_lead'       => 'Tag on lead',
    'action_remove_tag_lead'    => 'Remove tag',
    'action_add_tag_conversation' => 'Tag on conversation',
    'action_move_to_stage'      => 'Move stage',
    'action_set_lead_source'    => 'Set source',
    'action_assign_to_user'     => 'Assign user',
    'action_add_note'           => 'Add note',
    'action_assign_ai_agent'    => 'AI Agent',
    'action_assign_chatbot_flow' => 'Chatbot',
    'action_close_conversation' => 'Close conversation',
    'action_send_whatsapp_message' => 'Send WA msg',
    'action_create_task'        => 'Create task',

    // ── Trigger labels (badge + sidebar + canvas) ───────────────────────
    'trigger_message_received'      => 'Message received',
    'trigger_conversation_created'  => 'New conversation',
    'trigger_lead_created'          => 'Lead created',
    'trigger_lead_stage_changed'    => 'Lead stage changed',
    'trigger_lead_won'              => 'Lead won',
    'trigger_lead_lost'             => 'Lead lost',
    'trigger_date_field'            => 'Date / Birthday',
    'trigger_recurring'             => 'Recurring',
    'trigger_recurring_full'        => 'Recurring (Weekly/Monthly)',

    // ── Form page: header ───────────────────────────────────────────────
    'name_placeholder'          => 'Automation name...',
    'status_active'             => 'Active',
    'status_inactive'           => 'Inactive',
    'btn_cancel'                => 'Cancel',
    'btn_save'                  => 'Save automation',

    // ── Form page: sidebar sections ─────────────────────────────────────
    'sidebar_trigger'           => 'Trigger',
    'sidebar_conditions'        => 'Conditions',
    'sidebar_actions'           => 'Actions',

    // ── Sidebar: trigger items ──────────────────────────────────────────
    'sidebar_message_received'      => 'Message received',
    'sidebar_conversation_created'  => 'New conversation',
    'sidebar_lead_created'          => 'Lead created',
    'sidebar_lead_stage_changed'    => 'Lead stage changed',
    'sidebar_lead_won'              => 'Lead won',
    'sidebar_lead_lost'             => 'Lead lost',
    'sidebar_date_field'            => 'Date / Birthday',
    'sidebar_recurring'             => 'Recurring',

    // ── Sidebar: condition items ────────────────────────────────────────
    'sidebar_cond_message_body'     => 'Message body',
    'sidebar_cond_lead_source'      => 'Lead source',
    'sidebar_cond_lead_tag'         => 'Lead tag',
    'sidebar_cond_conversation_tag' => 'Conversation tag',

    // ── Sidebar: action items ───────────────────────────────────────────
    'sidebar_act_add_tag_lead'          => 'Add tag to lead',
    'sidebar_act_remove_tag_lead'       => 'Remove tag from lead',
    'sidebar_act_add_tag_conversation'  => 'Tag on conversation',
    'sidebar_act_move_to_stage'         => 'Move to stage',
    'sidebar_act_set_lead_source'       => 'Set lead source',
    'sidebar_act_assign_to_user'        => 'Assign to user',
    'sidebar_act_add_note'              => 'Add note',
    'sidebar_act_assign_ai_agent'       => 'Assign AI agent',
    'sidebar_act_assign_chatbot_flow'   => 'Assign chatbot',
    'sidebar_act_transfer_to_department' => 'Transfer to department',
    'sidebar_act_close_conversation'    => 'Close conversation',
    'sidebar_act_send_whatsapp_message' => 'Send WhatsApp msg',
    'sidebar_act_schedule_whatsapp_message' => 'Schedule WhatsApp msg',
    'sidebar_act_assign_campaign'       => 'Assign campaign',
    'sidebar_act_set_utm_params'        => 'Set UTM parameters',
    'sidebar_act_create_task'           => 'Create task',

    // ── Canvas: placeholders & group labels ─────────────────────────────
    'trigger_placeholder'       => 'Select a <strong>Trigger</strong> from the left panel to begin',
    'conditions_label'          => 'IF conditions are met...',
    'actions_label'             => 'THEN execute...',
    'add_action_btn'            => 'Add action',

    // ── Node type labels ────────────────────────────────────────────────
    'node_type_trigger'         => 'Trigger',
    'node_type_condition'       => 'Condition',
    'node_type_action'          => 'Action',

    // ── Trigger config: channel ─────────────────────────────────────────
    'label_channel'             => 'Channel',
    'channel_both'              => 'WhatsApp and Instagram',
    'channel_whatsapp'          => 'WhatsApp only',
    'channel_instagram'         => 'Instagram only',

    // ── Trigger config: pipeline / stage ────────────────────────────────
    'label_pipeline'            => 'Pipeline',
    'label_pipeline_optional'   => 'optional',
    'any_pipeline'              => 'Any pipeline',
    'label_target_stage'        => 'Target stage',
    'any_stage'                 => 'Any stage',

    // ── Trigger config: source ──────────────────────────────────────────
    'label_source'              => 'Source',
    'any_source'                => 'Any source',

    // ── Trigger config: date field ──────────────────────────────────────
    'label_date_field'          => 'Date field',
    'date_field_birthday'       => 'Birthday (native field)',
    'date_field_native_group'   => 'Native field',
    'date_field_custom_group'   => 'Custom fields',
    'date_field_custom_prefix'  => 'Field:',
    'label_days_before'         => 'Days in advance',
    'days_before_hint'          => '0 = on the same day',
    'label_repeat_yearly'       => 'Repeat yearly (e.g. birthdays)',

    // ── Trigger config: recurring ───────────────────────────────────────
    'label_recurrence_type'     => 'Recurrence type',
    'recurrence_weekly'         => 'Weekly',
    'recurrence_monthly'        => 'Monthly',
    'label_month_days'          => 'Days of month',
    'month_days_hint'           => 'separate with commas: 10, 20',
    'month_days_placeholder'    => '10, 20',
    'label_send_time'           => 'Send time',
    'label_filter_leads'        => 'Filter leads by',
    'filter_all'                => 'All leads',
    'filter_tag'                => 'Specific tag',
    'filter_stage'              => 'Pipeline stage',
    'filter_tag_placeholder'    => 'Tag name (e.g. Parents)',
    'label_daily_limit'         => 'Daily limit',
    'label_delay_between'       => 'Delay between sends (s)',
    'recurring_safety_note'     => 'Only sends to leads with an existing WhatsApp conversation. Delay between sends to prevent blocking.',
    'no_trigger_config'         => 'No configuration needed for this trigger.',

    // ── Weekday abbreviations ───────────────────────────────────────────
    'day_sun'                   => 'Sun',
    'day_mon'                   => 'Mon',
    'day_tue'                   => 'Tue',
    'day_wed'                   => 'Wed',
    'day_thu'                   => 'Thu',
    'day_fri'                   => 'Fri',
    'day_sat'                   => 'Sat',

    // ── Condition config: operators ─────────────────────────────────────
    'operator_contains'         => 'contains',
    'operator_not_contains'     => 'does not contain',
    'operator_equals'           => 'equals',
    'operator_starts_with'      => 'starts with',
    'operator_is'               => 'is',
    'operator_is_not'           => 'is not',

    // ── Condition config: labels ────────────────────────────────────────
    'label_operator'            => 'Operator',
    'label_value'               => 'Value',
    'placeholder_keyword'       => 'Keyword...',
    'label_origin'              => 'Source',
    'placeholder_select'        => 'Select...',
    'label_tag'                 => 'Tag',

    // ── Action config: labels ───────────────────────────────────────────
    'label_tags'                => 'Tags',
    'label_stage'               => 'Stage',
    'placeholder_pipeline'      => 'Pipeline...',
    'placeholder_stage'         => 'Stage...',
    'label_user'                => 'User',
    'label_note_text'           => 'Note text',
    'placeholder_note'          => 'Type the note...',
    'label_ai_agent'            => 'AI Agent',
    'no_ai_agents'              => 'No active AI agent (WhatsApp).',
    'label_flow'                => 'Flow',
    'no_chatbot_flows'          => 'No active chatbot flow.',
    'label_department'          => 'Department',
    'no_departments'            => 'No active department.',
    'close_conversation_info'   => 'The conversation linked to the lead will be closed automatically.',
    'label_campaign'            => 'Campaign',
    'no_campaigns'              => 'No campaigns registered.',

    // ── Action config: UTM ──────────────────────────────────────────────
    'utm_source'                => 'UTM Source',
    'utm_medium'                => 'UTM Medium',
    'utm_campaign'              => 'UTM Campaign',
    'utm_term'                  => 'UTM Term',
    'utm_content'               => 'UTM Content',
    'utm_optional'              => 'optional',
    'utm_placeholder_source'    => 'e.g. google',
    'utm_placeholder_medium'    => 'e.g. cpc',
    'utm_placeholder_campaign'  => 'e.g. black-friday',
    'utm_placeholder_term'      => 'e.g. crm+software',
    'utm_placeholder_content'   => 'e.g. top-banner',
    'utm_blank_hint'            => 'Leave blank the fields you do not want to change.',

    // ── Action config: WhatsApp message ─────────────────────────────────
    'label_message'             => 'Message',
    'placeholder_message'       => 'Type the message...',
    'no_whatsapp_instance'      => 'No WhatsApp instance connected.',

    // ── Action config: Schedule message ─────────────────────────────────
    'label_send_after'          => 'Send after',
    'label_unit'                => 'Unit',
    'unit_hours'                => 'Hours',
    'unit_days'                 => 'Days',

    // ── Action config: Create task ──────────────────────────────────────
    'label_subject'             => 'Subject',
    'placeholder_subject'       => 'Call @{{contact_name}}',
    'label_description'         => 'Description',
    'placeholder_description'   => 'Task details...',
    'label_task_type'           => 'Type',
    'task_type_call'            => 'Call',
    'task_type_email'           => 'Email',
    'task_type_task'            => 'Task',
    'task_type_visit'           => 'Visit',
    'task_type_whatsapp'        => 'WhatsApp',
    'task_type_meeting'         => 'Meeting',
    'label_priority'            => 'Priority',
    'priority_low'              => 'Low',
    'priority_medium'           => 'Medium',
    'priority_high'             => 'High',
    'label_due_days'            => 'Due in (days)',
    'label_due_time'            => 'Time',
    'label_assign_to'           => 'Assign to',
    'assign_auto'               => 'Automatic (lead owner)',

    // ── Tag widget ──────────────────────────────────────────────────────
    'tag_placeholder'           => 'Type or select...',
    'tag_add_new'               => 'Add',
    'tag_no_suggestions'        => 'No suggestions',

    // ── Save validation toasts ──────────────────────────────────────────
    'validation_name_required'  => 'Please enter the automation name.',
    'validation_trigger_required' => 'Please select a trigger.',
    'validation_select_tag'     => 'Select at least one tag.',
    'validation_select_stage'   => 'Select the target stage.',
    'validation_select_source'  => 'Select the source.',
    'validation_select_user'    => 'Select the user.',
    'validation_note_required'  => 'Enter the note text.',
    'validation_select_ai_agent' => 'Select the AI agent.',
    'validation_select_flow'    => 'Select the flow.',
    'validation_select_department' => 'Select the department.',
    'validation_message_required' => 'Enter the message.',
    'validation_schedule_message_required' => 'Enter the message to schedule.',
    'validation_delay_min'      => 'Delay must be at least 1.',
    'validation_select_campaign' => 'Select the campaign.',
    'validation_utm_required'   => 'Fill in at least one UTM field.',
    'validation_subject_required' => 'Enter the task subject.',
    'validation_action_required' => 'Add at least one action.',
    'validation_recurring_days' => 'Select at least one day for recurrence.',

    // ── Save toasts ─────────────────────────────────────────────────────
    'toast_created'             => 'Automation created.',
    'toast_updated'             => 'Automation updated.',
    'toast_save_error'          => 'Failed to save.',
    'toast_comm_error'          => 'Communication error.',

    // ── Misc ────────────────────────────────────────────────────────────
    'toast_select_action_hint'  => 'Select an action from the left panel.',
    'back'                      => 'Back',
];
