<?php

return [

    // ─── Index page ─────────────────────────────────────────────────────────
    'page_title'                   => 'Chatbot Builder',
    'new_flow'                     => 'New Flow',
    'empty_title'                  => 'No flows created yet',
    'empty_description'            => 'Create a flow to automatically serve your contacts on WhatsApp.',
    'empty_cta'                    => 'Create first flow →',
    'last_edit'                    => 'Last edited:',
    'created'                      => 'Created:',
    'nodes_singular'               => 'node',
    'nodes_plural'                 => 'nodes',
    'attended'                     => 'served',
    'activate'                     => 'Activate',
    'deactivate'                   => 'Deactivate',

    // Index card dropdown
    'dropdown_edit'                => 'Edit',
    'dropdown_results'             => 'Results',
    'dropdown_test'                => 'Test',
    'dropdown_link'                => 'Link',
    'dropdown_embed'               => 'Embed',
    'dropdown_delete'              => 'Delete',

    // Index badges
    'badge_active'                 => 'Active',
    'badge_inactive'               => 'Inactive',
    'badge_catch_all'              => 'Catch-all',

    // Index delete modal
    'delete_modal_title'           => 'Delete flow?',
    'delete_modal_text'            => 'The flow <strong>:name</strong> will be permanently removed.<br>This action cannot be undone.',
    'delete_modal_cancel'          => 'Cancel',
    'delete_modal_confirm'         => 'Delete',

    // Index toastr
    'toast_link_copied'            => 'Link copied!',
    'toast_toggle_error'           => 'Error changing flow status.',

    // ─── Test chat sidebar ──────────────────────────────────────────────────
    'test_title'                   => 'Testing flow',
    'test_subtitle'                => 'Simulation · no real messages sent',
    'test_input_placeholder'       => 'Type your response…',
    'test_restart'                 => 'Restart',
    'test_done'                    => 'Flow completed',
    'test_hint'                    => 'Enter to send · Shift+Enter for new line',
    'test_server_error'            => 'Error communicating with the server.',

    // ─── Embed modal (index + edit + builder) ───────────────────────────────
    'embed_modal_title'            => 'Installation code',
    'embed_modal_paste_before'     => 'Paste this code before <code>&lt;/body&gt;</code> on your website:',
    'embed_modal_paste_fullpage'   => 'To display the chatbot in <strong>full page</strong>, paste the code below in the <code>&lt;body&gt;</code> of the dedicated page:',
    'embed_modal_paste_inline'     => 'To display the chatbot <strong>embedded on the page</strong>, paste the code below where you want it to appear:',
    'embed_modal_hint_fullpage'    => '<i class="bi bi-info-circle"></i> The <code>&lt;div id="syncro-chat"&gt;</code> will fill the entire screen. Ideal for a dedicated chat page (e.g., <code>/support</code>).',
    'embed_modal_hint_inline'      => '<i class="bi bi-info-circle"></i> The <code>&lt;div id="syncro-chat"&gt;</code> is the chatbot container. Adjust <code>width</code> and <code>height</code> as needed.',
    'embed_modal_bubble_hint'      => 'The floating widget will appear in the bottom-right corner:',
    'embed_copy_button'            => 'Copy code',
    'embed_copied'                 => 'Copied!',
    'embed_widget_hint'            => 'The widget will appear in the bottom-right corner of your website.',
    'embed_public_link'            => 'Public link',
    'embed_public_link_hint'       => 'Use this link in campaigns, social media, or Instagram bio. No website needed.',
    'embed_public_link_copied'     => 'Link copied!',
    'embed_code_label'             => 'Embed code',
    'embed_copy'                   => 'Copy',

    // ─── Form page (create/edit) ────────────────────────────────────────────
    'form_title_new'               => 'New Flow',
    'form_title_edit'              => 'Edit Flow',
    'form_back'                    => 'Back',

    // Form sections
    'form_section_channel'         => 'Channel',
    'form_section_identification'  => 'Identification',
    'form_section_widget'          => 'Widget Appearance',
    'form_section_trigger'         => 'Automatic Trigger',
    'form_section_variables'       => 'Session Variables',
    'form_section_status'          => 'Status',

    // Form fields
    'form_flow_name'               => 'Flow name',
    'form_flow_name_placeholder'   => 'E.g.: Lead Qualification',
    'form_description'             => 'Description',
    'form_description_placeholder' => 'What is this flow for?',
    'form_slug'                    => 'Slug (public URL)',
    'form_slug_placeholder'        => 'my-chatbot',
    'form_bot_name'                => 'Bot name',
    'form_bot_name_placeholder'    => 'E.g.: Ana, Sofia, Assistant...',
    'form_bot_name_hint'           => 'Appears in the chat header.',
    'form_bot_avatar'              => 'Bot avatar',
    'form_bot_avatar_hint'         => 'Choose an avatar or click the last icon to upload a custom image.',
    'form_welcome_message'         => 'Welcome message',
    'form_welcome_placeholder'     => 'Hello! 👋 Can I help you?',
    'form_welcome_hint'            => 'Appears as a floating bubble above the chat button after 3 seconds. Leave empty to disable. (Bubble mode only)',
    'form_widget_type'             => 'Widget type',
    'form_widget_bubble'           => 'Bubble',
    'form_widget_bubble_desc'      => 'Floating button in the corner',
    'form_widget_inline'           => 'Inline / Page',
    'form_widget_inline_desc'      => 'Embedded in a page element',
    'form_bubble_hint'             => 'Add <code>&lt;script src="..." data-token="..."&gt;&lt;/script&gt;</code> before <code>&lt;/body&gt;</code>.',
    'form_inline_hint'             => 'Add <code>&lt;div id="syncro-chat"&gt;&lt;/div&gt;</code> where you want the chat, and the <code>&lt;script&gt;</code> at the end of the body.',
    'form_button_color'            => 'Button color',
    'form_button_color_hint'       => 'Sets the color of the chat button, header, and sent message bubbles.',
    'form_trigger_keywords'        => 'Trigger keywords',
    'form_trigger_placeholder'     => 'hi, hello, good morning',
    'form_trigger_hint'            => 'Comma separated. Leave empty for manual assignment only. When a contact sends a message containing one of these words, the flow will start automatically.',
    'form_variables'               => 'Variables',
    'form_variables_placeholder'   => 'name, email, interest',
    'form_variables_hint'          => 'Variable names the flow will collect. E.g.: <code>name</code> → use <code>{{name}}</code> in messages.',
    'form_active_label'            => 'Flow active',
    'form_active_hint'             => 'When active, the flow responds automatically to messages.',

    // Form buttons
    'form_save_changes'            => 'Save changes',
    'form_create_and_edit'         => 'Create and edit nodes',
    'form_cancel'                  => 'Cancel',
    'form_open_builder'            => 'Open Node Builder',

    // ─── Edit page (builder wrapper) ────────────────────────────────────────
    'edit_builder_title'           => 'Builder:',
    'edit_back'                    => 'Back',
    'edit_flow_settings'           => 'Flow settings',
    'edit_all_flows'               => 'All flows',
    'edit_embed'                   => 'Embed',
    'edit_embed_title'             => 'Embed Code',
    'edit_embed_paste'             => 'Paste this code before <code>&lt;/body&gt;</code> on your website:',
    'edit_copy'                    => 'Copy',
    'edit_copied'                  => 'Copied!',

    // ─── Builder page ───────────────────────────────────────────────────────

    // Builder header
    'builder_name_placeholder'     => 'Flow name...',
    'builder_active'               => 'Active',
    'builder_inactive'             => 'Inactive',
    'builder_test'                 => 'Test',
    'builder_embed'                => 'Embed',
    'builder_config'               => 'Settings',
    'builder_save'                 => 'Save',

    // Builder sidebar — blocks
    'sidebar_blocks'               => 'Blocks',
    'sidebar_message'              => 'Message',
    'sidebar_input'                => 'Question',
    'sidebar_condition'            => 'Condition',
    'sidebar_action'               => 'Action',
    'sidebar_delay'                => 'Wait',
    'sidebar_end'                  => 'End',
    'sidebar_cards'                => 'Cards',

    // Builder sidebar — config
    'sidebar_config'               => 'Settings',
    'sidebar_variables'            => 'Variables',
    'sidebar_catch_all'            => 'Default flow (catch-all)',
    'sidebar_catch_all_hint'       => 'Activates when no keyword matches. Only 1 per tenant.',

    // Builder sidebar — templates
    'sidebar_templates'            => 'Templates',
    'sidebar_use_template'         => 'Use template',

    // Builder node types
    'node_message'                 => 'Message',
    'node_input'                   => 'Question',
    'node_condition'               => 'Condition',
    'node_action'                  => 'Action',
    'node_delay'                   => 'Wait',
    'node_end'                     => 'End',
    'node_cards'                   => 'Cards',
    'node_start'                   => 'Start',
    'node_start_desc'              => 'When visitor sends a message',

    // Builder node summaries
    'summary_empty_message'        => 'Empty message',
    'summary_empty_question'       => 'Empty question',
    'summary_condition_if'         => 'If :variable...',
    'summary_empty_condition'      => 'Empty condition',
    'summary_seconds'              => ':count seconds',
    'summary_finalize'             => 'Finalize',
    'summary_cards_count'          => ':count card(s)',
    'summary_finalize_conversation'=> 'Finalize conversation',

    // Builder preview
    'preview_image'                => 'Image',
    'preview_option'               => 'Option',

    // Builder add step
    'add_block'                    => 'Add block',
    'add_step'                     => 'Add',

    // Builder edit panel — move/delete
    'panel_move_up'                => 'Up',
    'panel_move_down'              => 'Down',

    // Builder edit panel — buttons section
    'panel_buttons'                => 'Buttons',
    'panel_button_placeholder'     => 'Button text',
    'panel_add_button'             => 'Add button',
    'panel_remove'                 => 'Remove',

    // ─── Message node ───────────────────────────────────────────────────────
    'msg_text_label'               => 'Message text',
    'msg_text_placeholder'         => 'Type the message...',
    'msg_click_to_change_image'    => 'Click to change image',
    'msg_click_to_add_image'       => 'Click to add image',
    'msg_upload_image'             => 'Upload image',

    // ─── Input node ─────────────────────────────────────────────────────────
    'input_question_label'         => 'Question for the visitor',
    'input_question_placeholder'   => 'Type the question...',
    'input_field_type'             => 'Field type',
    'input_save_to'                => 'Save to',
    'input_save_none'              => 'Don\'t save',
    'input_show_buttons'           => 'Show quick reply buttons',

    // Input field types
    'field_type_text'              => 'Free text',
    'field_type_name'              => 'Name',
    'field_type_email'             => 'Email',
    'field_type_phone'             => 'Phone',
    'field_type_number'            => 'Number',
    'field_type_buttons'           => 'Quick reply buttons',

    // ─── Condition node ─────────────────────────────────────────────────────
    'condition_variable_label'     => 'Variable to check',
    'condition_select'             => 'Select...',
    'condition_hint'               => 'Each branch below defines a condition. E.g.: <strong>"If :variable equals X, do..."</strong>',

    // Condition operators
    'op_equals'                    => 'Equals',
    'op_not_equals'                => 'Not equals',
    'op_contains'                  => 'Contains',
    'op_starts_with'               => 'Starts with',
    'op_ends_with'                 => 'Ends with',
    'op_gt'                        => 'Greater than',
    'op_lt'                        => 'Less than',

    // Condition branch sentence fragments
    'op_sentence_equals'           => 'equals',
    'op_sentence_not_equals'       => 'is different from',
    'op_sentence_contains'         => 'contains',
    'op_sentence_starts_with'      => 'starts with',
    'op_sentence_ends_with'        => 'ends with',
    'op_sentence_gt'               => 'is greater than',
    'op_sentence_lt'               => 'is less than',

    // ─── Action node ────────────────────────────────────────────────────────
    'action_type_label'            => 'Action type',

    // Action types
    'action_create_lead'           => 'Create lead',
    'action_change_stage'          => 'Move to stage',
    'action_add_tag'               => 'Add tag',
    'action_remove_tag'            => 'Remove tag',
    'action_save_variable'         => 'Save variable',
    'action_close_conversation'    => 'Close conversation',
    'action_assign_human'          => 'Transfer to human',
    'action_send_webhook'          => 'Send webhook',
    'action_set_custom_field'      => 'Set custom field',
    'action_send_whatsapp'         => 'Send WhatsApp',
    'action_create_task'           => 'Create task',
    'action_redirect'              => 'Redirect (URL)',

    // Action: create lead
    'action_name'                  => 'Name',
    'action_email'                 => 'Email',
    'action_phone'                 => 'Phone',
    'action_stage'                 => 'Stage',
    'action_select_variable'       => 'Select variable...',

    // Action: change stage
    'action_target_stage'          => 'Target stage',

    // Action: add/remove tag
    'action_tag'                   => 'Tag',

    // Action: save variable
    'action_variable'              => 'Variable',
    'action_value'                 => 'Value',

    // Action: webhook
    'action_method'                => 'Method',
    'action_url'                   => 'URL',
    'action_json_body'             => 'JSON Body',

    // Action: set custom field
    'action_field'                 => 'Field',
    'action_field_value'           => 'Value',

    // Action: send whatsapp
    'action_destination'           => 'Destination',
    'action_phone_mode_variable'   => 'Flow variable',
    'action_phone_mode_custom'     => 'Fixed number',
    'action_phone_variable'        => 'Variable with phone',
    'action_phone_number'          => 'Number (with area code)',
    'action_wa_message'            => 'Message',
    'action_wa_hint'               => 'Sent via the connected WhatsApp instance.',

    // Action: create task
    'action_task_subject'          => 'Task subject',
    'action_task_subject_placeholder' => 'Call {{name}}',
    'action_task_description'      => 'Description',
    'action_task_desc_placeholder' => 'Task details...',
    'action_task_type'             => 'Type',
    'action_task_priority'         => 'Priority',
    'action_task_due_days'         => 'Due (days)',
    'action_task_due_time'         => 'Time',
    'action_task_assign_to'        => 'Assign to',
    'action_task_assign_auto'      => 'Automatic (lead owner)',
    'action_task_assign_user'      => 'Specific user',
    'action_task_user'             => 'User',
    'action_task_hint'             => 'Creates a task linked to the conversation lead.',

    // Task types
    'task_type_call'               => 'Call',
    'task_type_email'              => 'Email',
    'task_type_task'               => 'Task',
    'task_type_visit'              => 'Visit',
    'task_type_whatsapp'           => 'WhatsApp',
    'task_type_meeting'            => 'Meeting',

    // Task priorities
    'priority_low'                 => 'Low',
    'priority_medium'              => 'Medium',
    'priority_high'                => 'High',

    // Action: redirect
    'action_redirect_url'          => 'Destination URL',
    'action_redirect_open_in'      => 'Open in',
    'action_redirect_new_tab'      => 'New tab',
    'action_redirect_same_tab'     => 'Same tab',
    'action_redirect_hint'         => 'Redirects the visitor to the specified URL.',

    // ─── Delay node ─────────────────────────────────────────────────────────
    'delay_seconds_label'          => 'Wait seconds',

    // ─── End node ───────────────────────────────────────────────────────────
    'end_message_label'            => 'Closing message (optional)',
    'end_message_placeholder'      => 'Closing message...',

    // ─── Cards node ─────────────────────────────────────────────────────────
    'card_title_placeholder'       => 'Title',
    'card_description_placeholder' => 'Description',
    'card_button_placeholder'      => 'Button text (optional)',
    'card_button_action_reply'     => 'Continue flow',
    'card_button_action_url'       => 'Open link',
    'card_url_placeholder'         => 'URL',
    'card_value_placeholder'       => 'Value sent',
    'card_remove'                  => 'Remove',
    'card_add'                     => 'Add card',

    // ─── Branches ───────────────────────────────────────────────────────────
    'branch_option'                => 'Option :number',
    'branch_default'               => 'Default',
    'branch_default_hint'          => 'When no option matches',
    'branch_max_chars'             => 'Max 24 characters',
    'branch_remove'                => 'Remove option',
    'branch_add'                   => 'Add option',
    'branch_operator'              => 'Operator',
    'branch_value'                 => 'Value',
    'branch_condition_sentence'    => 'If <strong>:variable</strong> :operator <strong>:value</strong>',

    // ─── Variables modal ────────────────────────────────────────────────────
    'vars_modal_title'             => 'Flow variables',
    'vars_placeholder'             => 'variable_name',
    'vars_system_label'            => 'System variables:',
    'vars_hint_label'              => 'Variables:',
    'vars_insert_title'            => 'Insert',

    // ─── Variables toastr ───────────────────────────────────────────────────
    'toast_var_exists'             => 'Variable already exists',

    // ─── Builder embed modal ────────────────────────────────────────────────
    'builder_embed_title'          => 'Installation code',
    'builder_embed_paste'          => 'Paste this code before <code>&lt;/body&gt;</code> on your website:',
    'builder_embed_copy'           => 'Copy code',
    'builder_embed_copied'         => 'Copied!',

    // ─── Templates modal (builder) ──────────────────────────────────────────
    'tpl_modal_title'              => 'Ready-made templates',
    'tpl_search_placeholder'       => 'Search by niche... e.g.: dentist, restaurant, gym',
    'tpl_empty'                    => 'No templates found for this search.',
    'tpl_nodes'                    => 'nodes',
    'tpl_variables'                => 'variables',
    'tpl_confirm_replace'          => 'This will replace all current nodes. Continue?',
    'tpl_loaded'                   => 'Template ":name" loaded!',

    // Template categories
    'tpl_category_all'             => 'All',
    'tpl_category_geral'           => 'General',
    'tpl_category_imoveis'         => 'Real Estate',
    'tpl_category_saude'           => 'Health',
    'tpl_category_estetica'        => 'Beauty',
    'tpl_category_fitness'         => 'Fitness',
    'tpl_category_educacao'        => 'Education',
    'tpl_category_alimentacao'     => 'Food',
    'tpl_category_varejo'          => 'Retail',
    'tpl_category_servicos'        => 'Services',
    'tpl_category_automotivo'      => 'Automotive',
    'tpl_category_tecnologia'      => 'Technology',
    'tpl_category_eventos'         => 'Events',
    'tpl_category_turismo'         => 'Tourism',
    'tpl_category_financeiro'      => 'Finance',
    'tpl_category_construcao'      => 'Construction',

    // ─── Builder toastr messages ────────────────────────────────────────────
    'toast_flow_saved'             => 'Flow saved successfully!',
    'toast_save_error'             => 'Error saving',
    'toast_save_flow_error'        => 'Error saving flow',
    'toast_name_required'          => 'Please enter the flow name',
    'toast_upload_error'           => 'Error uploading image',
    'toast_catch_all_on'           => 'Flow set as catch-all',
    'toast_catch_all_off'          => 'Catch-all disabled',
    'toast_update_error'           => 'Error updating',

    // ─── Onboarding wizard ──────────────────────────────────────────────────
    'onboarding_title'             => 'New Chatbot',
    'onboarding_step_counter'      => 'Step :current of :total',
    'onboarding_back'              => 'Back',
    'onboarding_next'              => 'Next',
    'onboarding_create'            => 'Create Chatbot',
    'onboarding_creating'          => 'Creating...',
    'onboarding_skip'              => 'Skip',

    // Wizard step: channel
    'wizard_channel_question'      => 'Which channel?',
    'wizard_channel_subtitle'      => 'Choose where your chatbot will operate.',
    'wizard_channel_whatsapp_desc' => 'Triggered by keywords',
    'wizard_channel_instagram_desc'=> 'DMs and automatic replies',
    'wizard_channel_website_desc'  => 'Chat widget on your website',

    // Wizard step: name
    'wizard_name_question'         => 'What do you want to call your chatbot?',
    'wizard_name_subtitle'         => 'A short and descriptive name.',
    'wizard_name_placeholder'      => 'E.g.: Lead Qualifier, Support...',
    'wizard_description_label'     => 'DESCRIPTION',
    'wizard_description_placeholder'=> 'Briefly describe the purpose of this flow...',

    // Wizard step: template
    'wizard_template_question'     => 'Choose a template or start from scratch',
    'wizard_template_subtitle'     => 'Ready-made templates speed up creation.',
    'wizard_template_search'       => 'Search template...',
    'wizard_template_from_scratch' => 'Start from scratch',

    // Wizard step: widget settings
    'wizard_widget_question'       => 'Configure your widget',
    'wizard_widget_subtitle'       => 'Customize the chat appearance on your website.',
    'wizard_widget_bot_name'       => 'Bot name',
    'wizard_widget_bot_placeholder'=> 'E.g.: Ana, Sofia, Assistant...',
    'wizard_widget_avatar'         => 'Avatar',
    'wizard_widget_upload'         => 'Custom upload',
    'wizard_widget_welcome'        => 'Welcome message',
    'wizard_widget_welcome_placeholder' => 'Hello! 👋 How can I help you?',
    'wizard_widget_type'           => 'Widget type',
    'wizard_widget_bubble'         => 'Bubble',
    'wizard_widget_bubble_desc'    => 'Floating bubble in the corner',
    'wizard_widget_inline'         => 'Inline / Page',
    'wizard_widget_inline_desc'    => 'Embedded in the page',
    'wizard_widget_color'          => 'Widget color',

    // Wizard step: trigger keywords
    'wizard_keywords_question'     => 'Trigger keywords',
    'wizard_keywords_subtitle'     => 'When the contact sends one of these words, the flow starts automatically. Separate with commas.',
    'wizard_keywords_placeholder'  => 'hi, hello, good morning, menu, price',
    'wizard_keywords_hint'         => 'If no keywords are defined, the flow will only be activated manually.',

    // Wizard step: review
    'wizard_review_question'       => 'All set? Review before creating',
    'wizard_review_subtitle'       => 'Confirm your chatbot information.',
    'wizard_review_empty'          => 'No fields filled in.',

    // Wizard review labels
    'review_channel'               => 'Channel',
    'review_name'                  => 'Name',
    'review_description'           => 'Description',
    'review_template'              => 'Template',
    'review_bot_name'              => 'Bot name',
    'review_avatar'                => 'Avatar',
    'review_welcome'               => 'Welcome',
    'review_widget_type'           => 'Widget type',
    'review_color'                 => 'Color',
    'review_keywords'              => 'Keywords',
    'review_widget_bubble'         => 'Bubble (floating)',
    'review_widget_inline'         => 'Inline (page)',
    'review_from_scratch'          => 'From scratch',

    // Wizard validation
    'wizard_select_channel'        => 'Select a channel.',
    'wizard_name_required'         => 'Give your chatbot a name.',

    // Wizard toastr
    'toast_created'                => 'Chatbot created successfully!',
    'toast_create_error'           => 'Error creating chatbot.',
    'toast_connection_error'       => 'Connection error. Try again.',

    // ─── Results page ───────────────────────────────────────────────────────
    'results_title'                => 'Results',
    'results_back'                 => 'Back',

    // KPIs
    'results_total'                => 'Total conversations',
    'results_finished'             => 'Finished',
    'results_in_progress'          => 'In progress',
    'results_leads_created'        => 'Leads created',

    // Table
    'results_table_title'          => 'Responses',
    'results_search_placeholder'   => 'Search...',
    'results_filter_all'           => 'All status',
    'results_filter_open'          => 'In progress',
    'results_filter_closed'        => 'Finished',
    'results_csv'                  => 'CSV',
    'results_delete_selected'      => 'Delete',
    'results_status'               => 'Status',
    'results_status_open'          => 'In progress',
    'results_status_closed'        => 'Finished',
    'results_view_conversation'    => 'View conversation',
    'results_no_messages'          => 'No messages found.',
    'results_empty'                => 'No results yet. Responses appear here when visitors interact with the chatbot.',
    'results_confirm_delete'       => 'Delete :count result(s)? This action cannot be undone.',
    'results_deleted'              => ':count result(s) deleted.',
    'results_load_error'           => 'Error loading conversation.',

];
