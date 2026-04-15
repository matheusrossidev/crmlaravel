<?php

return [
    // Menu
    'menu_title' => 'WhatsApp Templates',

    // Index
    'title'       => 'WhatsApp Templates',
    'subtitle'    => 'Pre-approved messages used to start conversations outside the 24-hour window.',
    'create'      => 'Create template',
    'sync'        => 'Sync with Meta',
    'sync_running'=> 'Syncing...',
    'open_meta'       => 'Open in Meta',
    'open_meta_title' => 'Open templates panel in Meta Business Manager',
    'empty_title' => 'No templates yet',
    'empty_body'  => 'Create your first template to send messages outside the 24-hour window.',

    // Table
    'col_name'       => 'Name',
    'col_instance'   => 'Instance',
    'col_language'   => 'Language',
    'col_category'   => 'Category',
    'col_status'     => 'Status',
    'col_last_sync'  => 'Last sync',
    'col_actions'    => 'Actions',

    // Status
    'status_approved' => 'Approved',
    'status_pending'  => 'Pending',
    'status_rejected' => 'Rejected',
    'status_paused'   => 'Paused',
    'status_disabled' => 'Disabled',
    'status_in_appeal'=> 'In appeal',

    // Categories
    'cat_utility'        => 'Utility',
    'cat_marketing'      => 'Marketing',
    'cat_authentication' => 'Authentication',
    'cat_utility_desc'        => 'Confirmations, reminders, order updates',
    'cat_marketing_desc'      => 'Promotions and news (higher Meta pricing)',
    'cat_authentication_desc' => 'OTP codes and 2FA',

    // Form labels
    'form_name'        => 'Template name',
    'form_name_hint'   => 'snake_case, no accents. E.g. appointment_reminder',
    'form_language'    => 'Language',
    'form_category'    => 'Category',
    'form_instance'    => 'Instance (WABA)',
    'form_header'      => 'Header (optional)',
    'form_header_none' => 'No header',
    'form_header_text' => 'Text',
    'form_header_image'=> 'Image',
    'form_header_video'=> 'Video',
    'form_header_doc'  => 'Document',
    'form_header_hint_media' => 'You provide the media when sending (upload in chat).',
    'form_body'        => 'Message body',
    'form_body_hint'   => 'Use {{1}}, {{2}} for variables. Max 1024 chars.',
    'form_samples'     => 'Variable examples',
    'form_samples_hint'=> 'Meta requires real examples to review your template.',
    'form_footer'      => 'Footer (optional)',
    'form_footer_hint' => 'Max 60 chars. No variables allowed.',
    'form_buttons'     => 'Buttons (optional)',
    'form_add_button'  => '+ Add button',
    'form_btn_type'    => 'Type',
    'form_btn_quick'   => 'Quick reply',
    'form_btn_url'     => 'URL',
    'form_btn_phone'   => 'Phone',
    'form_btn_copy'    => 'Copy code',
    'form_submit'      => 'Submit to Meta for review',

    // Preview
    'preview_title' => 'Preview',
    'preview_hint'  => 'How it will look on the customer\'s WhatsApp.',

    // Show
    'show_rejected_reason' => 'Rejection reason',
    'show_quality'         => 'Quality',
    'show_meta_id'         => 'Meta ID',
    'show_last_sync'       => 'Last sync',
    'show_delete'          => 'Delete template',
    'show_delete_confirm'  => 'Delete this template? It will also be removed from Meta.',

    // Modal
    'modal_title'        => 'Send template',
    'modal_select'       => 'Choose an approved template',
    'modal_search'       => 'Search by name...',
    'modal_variables'    => 'Fill variables',
    'modal_header_media' => 'Attach header media',
    'modal_send'         => 'Send',
    'modal_no_approved'  => 'No approved templates on this instance yet.',

    // Window 24h
    'window_closed_notice' => 'The 24-hour window is closed. Use an approved template to re-engage.',
    'window_closed_cta'    => 'Send template',
    'compose_template_btn' => 'Template',

    // Toasts
    'toast_created'      => 'Template submitted to Meta. Review takes 24–72h.',
    'toast_deleted'      => 'Template deleted.',
    'toast_sent'         => 'Template sent.',
    'toast_sync_done'    => 'Sync complete.',
    'toast_sync_error'   => 'Sync error: :msg',

    // Validation errors
    'err_name_invalid'           => 'Name must be snake_case, max 64 chars.',
    'err_variables_not_sequential'=> 'Variables must be sequential starting at {{1}}.',
    'err_sample_required'        => 'Provide examples for all variables.',
    'err_not_approved'           => 'Only approved templates can be sent.',
];
