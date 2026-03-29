<?php

return [
    'title'       => 'Lead Scoring',
    'subtitle'    => 'Configure scoring rules to automatically classify your leads',
    'new_rule'    => 'New Rule',
    'edit_rule'   => 'Edit Rule',
    'no_rules'    => 'No scoring rules configured.',
    'no_rules_sub' => 'Create rules to automatically score leads based on engagement, pipeline and profile.',

    // Table headers
    'col_name'      => 'Name',
    'col_category'  => 'Category',
    'col_event'     => 'Event',
    'col_points'    => 'Points',
    'col_cooldown'  => 'Cooldown',
    'col_status'    => 'Status',
    'col_actions'   => 'Actions',

    // Form
    'field_name'           => 'Rule name',
    'field_name_placeholder' => 'E.g.: Lead replied to message',
    'field_category'       => 'Category',
    'field_event'          => 'Trigger event',
    'field_points'         => 'Points',
    'field_points_help'    => 'Positive values add, negative values subtract.',
    'field_cooldown'       => 'Cooldown (hours)',
    'field_cooldown_help'  => 'Minimum time between fires of the same rule for the same lead. 0 = no limit.',
    'field_active'         => 'Active',

    // Categories
    'cat_engagement' => 'Engagement',
    'cat_pipeline'   => 'Pipeline',
    'cat_profile'    => 'Profile',

    // Event types
    'evt_message_received'   => 'Message received',
    'evt_message_sent_media' => 'Media sent by lead',
    'evt_fast_reply'         => 'Fast reply (< 5 min)',
    'evt_stage_advanced'     => 'Stage advanced',
    'evt_stage_regressed'    => 'Stage regressed',
    'evt_lead_won'           => 'Sale closed',
    'evt_lead_lost'          => 'Sale lost',
    'evt_profile_complete'   => 'Profile complete (email + company)',
    'evt_inactive_3d'        => 'Inactive for 3 days',
    'evt_inactive_7d'        => 'Stuck in stage for 7 days',

    // Actions
    'btn_save'   => 'Save',
    'btn_cancel' => 'Cancel',
    'btn_delete' => 'Delete',

    // Toasts
    'toast_created' => 'Rule created successfully!',
    'toast_updated' => 'Rule updated successfully!',
    'toast_deleted' => 'Rule deleted.',
    'toast_error'   => 'Error saving rule.',

    // Confirm
    'confirm_delete_title' => 'Delete rule?',
    'confirm_delete_msg'   => 'This action cannot be undone. Existing score logs will be preserved.',
    'confirm_delete_btn'   => 'Yes, delete',

    // Score display
    'score_label'    => 'Score',
    'score_high'     => 'Hot',
    'score_medium'   => 'Warm',
    'score_low'      => 'Cold',
    'breakdown'      => 'Breakdown',

    // Hours
    'hours_none' => 'No limit',
    'hours_unit' => ':count h',
];
