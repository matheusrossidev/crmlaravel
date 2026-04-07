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

    // ===== Phase 1: Structural filters and global limits =====
    'section_filters'      => 'Advanced filters and limits',
    'pipeline_filter'      => 'Pipeline filter',
    'pipeline_filter_help' => 'When set, this rule only fires for leads in this pipeline. Empty = any pipeline.',
    'any_pipeline'         => 'Any pipeline',
    'stage_filter'         => 'Stage filter',
    'stage_filter_help'    => 'Restricts the rule to a specific stage of the chosen pipeline.',
    'any_stage'            => 'Any stage',
    'valid_from'           => 'Valid from',
    'valid_until'          => 'Valid until',
    'max_triggers'         => 'Lifetime limit per lead',
    'max_triggers_help'    => 'How many times this rule can fire for the same lead. Empty = no limit.',
    'no_limit'             => 'No limit',

    // Global score limits (Fix 7)
    'global_limits'      => 'Global score limits',
    'global_limits_help' => 'Applied to all leads in this tenant. Score never goes below the minimum nor exceeds the maximum.',
    'score_min_label'    => 'Minimum score',
    'score_max_label'    => 'Maximum score',
    'no_max'             => 'No cap',
    'save_limits'        => 'Save limits',
    'limits_saved'       => 'Limits saved!',
];
