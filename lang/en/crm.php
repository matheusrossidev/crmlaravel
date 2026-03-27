<?php

return [
    // Page
    'title' => 'CRM',

    // Header buttons
    'filters'         => 'Filters',
    'export_leads'    => 'Export leads',
    'import_leads'    => 'Import leads',
    'new_lead'        => 'New Lead',
    'create_pipeline' => 'Create pipeline',

    // Filters
    'all_sources'     => 'All sources',
    'all_tags'        => 'All tags',
    'date_from'       => 'Date from',
    'date_to'         => 'Date to',
    'responsible'     => 'Responsible',
    'ai_agent'        => 'AI Agent',
    'apply'           => 'Apply',
    'clear'           => 'Clear',

    // Card actions
    'call'            => 'Call',
    'open_conversation' => 'Open conversation',
    'send_email'      => 'Send email',

    // Task bar (relative dates)
    'days_ago'        => 'd ago',
    'today'           => 'Today',
    'tomorrow'        => 'Tomorrow',

    // Board
    'drag_here'       => 'Drag leads here',
    'add_lead'        => 'Add lead',
    'no_stages'       => 'No stages configured in this pipeline.',

    // Won modal
    'won_title'       => 'Lead Won!',
    'won_desc'        => 'Enter the deal value (optional).',
    'won_placeholder' => 'Value (e.g.: 1500.00)',
    'skip'            => 'Skip',
    'confirm'         => 'Confirm',

    // Lost modal
    'lost_title'      => 'Lead Lost',
    'lost_desc'       => 'Select the loss reason (optional).',
    'no_reason'       => 'No reason',

    // Empty state
    'no_pipeline'           => 'No pipeline configured',
    'no_pipeline_desc'      => 'Create your first sales pipeline to start organizing your leads into stages and track your business progress.',
    'create_first_pipeline' => 'Create my first pipeline',

    // Pipeline drawer
    'create_new_pipeline' => 'Create new pipeline',
    'pipeline_name'       => 'Pipeline name',
    'pipeline_name_ph'    => 'E.g.: Sales 2025',
    'color'               => 'Color',
    'cancel'              => 'Cancel',
    'create_pipeline_btn' => 'Create pipeline',

    // Import modal
    'import_title'        => 'Import Leads',
    'template_title'      => 'Template spreadsheet',
    'template_desc'       => 'Includes the current pipeline stages as reference',
    'download'            => 'Download',
    'select_file'         => 'Select file',
    'file_formats'        => 'Formats: .xlsx, .xls, .csv — max 5 MB',
    'preview'             => 'Preview',
    'preview_title'       => 'Preview',
    'back'                => 'Back',
    'confirm_import'      => 'Confirm import',
    'col_name'            => 'Name',
    'col_phone'           => 'Phone',
    'col_email'           => 'Email',
    'col_value'           => 'Value',
    'col_stage'           => 'Stage',
    'col_tags'            => 'Tags',
    'col_source'          => 'Source',
    'col_created'         => 'Created at',

    // JS messages
    'lead_moved'          => 'Lead moved!',
    'error_move_lead'     => 'Error moving lead. Reload the page.',
    'new_lead_toast'      => 'New lead: :name',
    'error_create_pipeline' => 'Error creating pipeline. Please try again.',
    'select_file_first'   => 'Select a file before previewing.',
    'no_pipeline_selected' => 'No pipeline selected.',
    'analyzing'           => 'Analyzing...',
    'error_analyze_file'  => 'Error analyzing the file. Check the format and try again.',
    'token_missing'       => 'Token missing. Go back and try again.',
    'importing'           => 'Importing...',
    'import_success'      => ':count lead(s) imported successfully!',
    'import_skipped'      => ':count skipped.',
    'error_import'        => 'Error importing. Please try again.',
    'creating'            => 'Creating…',
    'pipeline_prefix'     => 'Pipeline: :name',
    'no_name_skip'        => '(no name — skipped)',
    'stage_not_found'     => 'Stage not found — the initial stage will be used',

    // Source labels
    'source_facebook'     => 'Facebook Ads',
    'source_google'       => 'Google Ads',
    'source_instagram'    => 'Instagram',
    'source_whatsapp'     => 'WhatsApp',
    'source_site'         => 'Website',
    'source_indicacao'    => 'Referral',
    'source_api'          => 'API',
    'source_manual'       => 'Manual',
    'source_outro'        => 'Other',
];
