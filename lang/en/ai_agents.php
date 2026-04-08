<?php

declare(strict_types=1);

return [

    // =========================================================================
    // INDEX PAGE (agents/index.blade.php)
    // =========================================================================

    'index_title'                  => 'AI Agent',
    'index_heading'                => 'AI Agents',
    'tokens_exhausted_btn'         => 'Tokens exhausted',
    'new_agent'                    => 'New Agent',

    // Empty state
    'empty_title'                  => 'No agents created yet',
    'empty_description'            => 'Create an AI agent to automatically reply in your chats.',
    'empty_cta'                    => 'Create first agent',

    // Agent card
    'badge_active'                 => 'Active',
    'badge_inactive'               => 'Inactive',
    'badge_no_tokens'              => 'No tokens',
    'objective_sales'              => 'Sales',
    'objective_support'            => 'Support',
    'objective_general'            => 'General',
    'channel_whatsapp'             => 'WhatsApp',
    'channel_instagram'            => 'Instagram',
    'channel_web_chat'             => 'Web Chat',
    'conversation_singular'        => 'conversation',
    'conversation_plural'          => 'conversations',
    'created_label'                => 'Created:',

    // Card dropdown
    'action_edit'                  => 'Edit',
    'action_test'                  => 'Test',
    'action_delete'                => 'Delete',
    'toggle_activate'              => 'Activate',
    'toggle_deactivate'            => 'Deactivate',

    // Delete modal
    'delete_modal_title'           => 'Delete agent?',
    'delete_modal_text'            => 'The agent will be permanently removed.<br>This action cannot be undone.',
    'delete_modal_cancel'          => 'Cancel',
    'delete_modal_confirm'         => 'Delete',

    // Test chat sidebar
    'test_chat_title'              => 'Agent',
    'test_chat_subtitle'           => 'Simulated conversation test',
    'test_chat_close'              => 'Close',
    'test_chat_placeholder'        => 'Type a message...',
    'test_chat_send'               => 'Send',
    'test_chat_reset'              => 'Reset conversation',
    'test_chat_reset_msg'          => 'Conversation reset. Type a message to start.',
    'test_chat_error_prefix'       => 'Error: ',
    'test_chat_error_generic'      => 'Failed to get a response.',
    'test_chat_error_connection'   => 'Connection error.',

    // Toastr (index)
    'toast_agent_deleted'          => 'Agent deleted.',
    'toast_delete_error'           => 'Error deleting agent.',

    // =========================================================================
    // TOKEN QUOTA SIDEBAR (inside index.blade.php)
    // =========================================================================

    'quota_sidebar_title'          => 'AI Tokens',
    'quota_sidebar_subtitle'       => 'Usage and increment packages',
    'quota_exhausted_title'        => 'Quota exhausted this month',
    'quota_exhausted_text'         => 'Your agent was paused automatically. Add more tokens to reactivate it.',
    'quota_usage_label'            => 'Current month usage',
    'quota_tokens_used'            => ':count tokens used',
    'quota_percent_limit'          => ':pct% of limit',
    'quota_limit_label'            => 'Limit: :count tokens/month',
    'quota_chart_title'            => 'Usage — last 7 days',
    'quota_choose_pack'            => 'Choose a package to continue',
    'quota_billing_title'          => 'Billing details (first purchase)',
    'quota_cpf_cnpj'               => 'CPF or CNPJ',
    'quota_email_nf'               => 'Email for invoice',
    'quota_pix_title'              => 'Pay via PIX',
    'quota_pix_copy'               => 'Copy PIX code',
    'quota_open_invoice'           => 'Open invoice',
    'quota_reactivation_notice'    => 'After payment, your agent will be automatically reactivated within 5 minutes.',
    'quota_no_packs'               => 'No packages available at this time.',
    'quota_no_packs_contact'       => 'Contact support for assistance.',
    'quota_buy_btn'                => 'Buy selected package',

    // Toastr (quota)
    'toast_select_pack'            => 'Select a package before continuing.',
    'toast_cpf_required'           => 'Please enter a CPF or CNPJ to continue.',
    'toast_email_required'         => 'Please enter a valid email.',
    'toast_billing_generated'      => 'Invoice generated! Complete the payment via PIX.',
    'toast_billing_error'          => 'Error generating invoice.',
    'toast_pix_copied'             => 'PIX code copied!',
    'toast_pix_copy_error'         => 'Could not copy automatically.',
    'toast_connection_error'       => 'Connection error. Please try again.',
    'toast_processing'             => 'Processing...',

    // =========================================================================
    // CREATE WIZARD (agents/create.blade.php)
    // =========================================================================

    'create_title'                 => 'New AI Agent',
    'wizard_back'                  => 'Back',
    'wizard_step_counter'          => 'Step :current of :total',
    'wizard_skip'                  => 'Skip this step',
    'wizard_skip_short'            => 'Skip',
    'wizard_next'                  => 'Next',
    'wizard_create_agent'          => 'Create Agent',
    'wizard_creating'              => 'Creating...',

    // Step 1: Name
    'step1_question'               => 'What should your agent be called?',
    'step1_subtitle'               => 'Give it a name that represents the agent\'s identity.',
    'step1_placeholder'            => 'E.g.: Ana, Victor, Sales Bot',

    // Step 2: Company
    'step2_question'               => 'Which company will use this agent?',
    'step2_subtitle'               => 'Optional — used so the agent can introduce itself correctly.',
    'step2_placeholder'            => 'E.g.: John\'s Store, Wellness Clinic',

    // Step 3: Objective
    'step3_question'               => 'What is the main objective?',
    'step3_subtitle'               => 'Defines the focus of the agent\'s responses.',
    'step3_sales'                  => 'Sales',
    'step3_sales_desc'             => 'Captures leads and drives negotiations',
    'step3_support'                => 'Support',
    'step3_support_desc'           => 'Resolves questions and problems',
    'step3_general'                => 'General',
    'step3_general_desc'           => 'General-purpose service',

    // Step 4: Communication style
    'step4_question'               => 'How should it communicate?',
    'step4_subtitle'               => 'Defines the tone of the agent\'s messages.',
    'step4_formal'                 => 'Formal',
    'step4_formal_desc'            => 'Professional and structured',
    'step4_normal'                 => 'Normal',
    'step4_normal_desc'            => 'Natural and friendly',
    'step4_casual'                 => 'Casual',
    'step4_casual_desc'            => 'Relaxed and informal',

    // Step 5: Language
    'step5_question'               => 'In which language?',
    'step5_subtitle'               => 'Default language for the agent\'s responses.',
    'step5_pt'                     => 'Portuguese',
    'step5_en'                     => 'English',
    'step5_es'                     => 'Spanish',

    // Step 6: Persona
    'step6_question'               => 'Describe the agent\'s personality',
    'step6_subtitle'               => 'How should the agent present itself and behave?',
    'step6_placeholder'            => 'E.g.: You are Ana, a virtual assistant at John\'s Store. You are friendly, patient and always focused on helping the customer find the ideal product...',

    // Step 7: Behavior
    'step7_question'               => 'Behavior rules',
    'step7_subtitle'               => 'What should the agent DO and NOT DO?',
    'step7_placeholder'            => 'E.g.: Always greet the customer by name. Never provide prices without confirming availability. Escalate serious complaints to a human...',

    // Step 8: Finish action
    'step8_question'               => 'Message when ending service',
    'step8_subtitle'               => 'What should the agent say when finishing the conversation?',
    'step8_placeholder'            => 'E.g.: Thank you for reaching out! If you need anything else, just let us know. Have a great day!',

    // Step 9: Knowledge base
    'step9_question'               => 'Knowledge base',
    'step9_subtitle'               => 'Information about your company, products, prices, policies...',
    'step9_placeholder'            => "Product A: \$99.90, available in blue and red.\nProduct B: \$149.00, delivery within 5 days.\nReturn policy: 7 days after purchase...",

    // Step 10: Channel
    'step10_question'              => 'Service channel',
    'step10_subtitle'              => 'Where will this agent operate?',
    'step10_whatsapp'              => 'WhatsApp',
    'step10_whatsapp_desc'         => 'Integration with WAHA / WhatsApp Web',
    'step10_web_chat'              => 'Web Chat',
    'step10_web_chat_desc'         => 'Widget on the company website',

    // Step 11: Review
    'step11_question'              => 'All good? Review before creating',
    'step11_subtitle'              => 'Confirm the agent information.',
    'review_empty'                 => 'No fields filled in.',

    // Review labels
    'review_name'                  => 'Name',
    'review_company'               => 'Company',
    'review_objective'             => 'Objective',
    'review_style'                 => 'Style',
    'review_language'              => 'Language',
    'review_persona'               => 'Personality',
    'review_behavior'              => 'Rules',
    'review_finish_action'         => 'Closing message',
    'review_knowledge'             => 'Knowledge base',
    'review_channel'               => 'Channel',

    // Toastr (create wizard)
    'toast_name_required'          => 'Please give the agent a name.',
    'toast_objective_required'     => 'Select the agent\'s objective.',
    'toast_style_required'         => 'Select the communication style.',
    'toast_language_required'      => 'Select the language.',
    'toast_channel_required'       => 'Select the service channel.',
    'toast_agent_created'          => 'Agent created! Redirecting to edit...',
    'toast_create_error'           => 'Error creating the agent. Please try again.',
    'toast_connection_error_create' => 'Connection error. Check your internet and try again.',

    // =========================================================================
    // ONBOARDING WIZARD (agents/onboarding.blade.php)
    // =========================================================================

    'onboarding_title'             => 'New AI Agent',

    // Step 1: Name
    'ob_step1_question'            => 'What do you want to call your agent?',
    'ob_step1_subtitle'            => 'A name to identify the agent (e.g.: Ana Sales, Support Bot).',
    'ob_step1_placeholder'         => 'E.g.: Ana, Sales Assistant...',

    // Step 2: Company
    'ob_step2_question'            => 'What\'s the company?',
    'ob_step2_subtitle'            => 'The agent will introduce itself representing this company.',
    'ob_step2_placeholder'         => 'E.g.: John\'s Store, Total Health Clinic...',

    // Step 3: Objective
    'ob_step3_question'            => 'What is the agent\'s objective?',
    'ob_step3_subtitle'            => 'This defines the base behavior of responses.',
    'ob_step3_sales'               => 'Sales',
    'ob_step3_sales_desc'          => 'Qualify leads and close deals',
    'ob_step3_support'             => 'Support',
    'ob_step3_support_desc'        => 'Answer questions and solve problems',
    'ob_step3_general'             => 'General',
    'ob_step3_general_desc'        => 'Versatile and informative service',

    // Step 4: Style
    'ob_step4_question'            => 'Communication style',
    'ob_step4_subtitle'            => 'How should the agent communicate with contacts.',
    'ob_step4_formal'              => 'Formal',
    'ob_step4_formal_desc'         => 'Professional and to the point',
    'ob_step4_normal'              => 'Normal',
    'ob_step4_normal_desc'         => 'Natural and friendly',
    'ob_step4_casual'              => 'Casual',
    'ob_step4_casual_desc'         => 'Relaxed and informal',

    // Step 5: Language
    'ob_step5_question'            => 'Response language',
    'ob_step5_subtitle'            => 'In which language should the agent respond.',
    'ob_step5_pt'                  => 'Português',
    'ob_step5_en'                  => 'English',
    'ob_step5_es'                  => 'Español',

    // Step 6: Persona
    'ob_step6_question'            => 'Agent persona',
    'ob_step6_subtitle'            => 'Describe the personality and profile of the virtual assistant.',
    'ob_step6_placeholder'         => 'E.g.: I\'m Ana, a sales consultant with 5 years of experience in real estate. I\'m friendly, attentive and always seek to understand the customer\'s needs...',

    // Step 7: Behavior
    'ob_step7_question'            => 'Behavior rules',
    'ob_step7_subtitle'            => 'Define what the agent SHOULD and SHOULD NOT do.',
    'ob_step7_placeholder'         => 'E.g.: SHOULD always ask the customer\'s name. SHOULD NOT give discounts without approval. SHOULD transfer to human when the customer gets upset...',

    // Step 8: Finish action
    'ob_step8_question'            => 'Closing message',
    'ob_step8_subtitle'            => 'What should the agent say when ending service.',
    'ob_step8_placeholder'         => 'E.g.: Thank you for reaching out! If you have any questions, just let us know.',

    // Step 9: Knowledge
    'ob_step9_question'            => 'Knowledge base',
    'ob_step9_subtitle'            => 'Paste information about your company, products, prices, FAQ, etc.',
    'ob_step9_placeholder'         => 'E.g.: Our company offers plans starting at $49/month. Business hours: Monday to Friday, 9am-6pm. Address: ...',

    // Step 10: Media
    'ob_step10_question'           => 'Media for sending',
    'ob_step10_subtitle'           => 'Upload images, PDFs, and catalogs that the agent can send to contacts during conversation.',
    'ob_step10_dropzone'           => 'Click or drag files here',
    'ob_step10_dropzone_hint'      => 'PNG, JPG, PDF, DOC — max 20 MB',
    'ob_step10_desc_placeholder'   => 'Describe when to send (e.g.: product catalog, price list)',
    'ob_step10_upload_btn'         => 'Upload',

    // Step 11: Channel
    'ob_step11_question'           => 'Service channel',
    'ob_step11_subtitle'           => 'Where the agent will operate.',
    'ob_step11_whatsapp'           => 'WhatsApp',
    'ob_step11_whatsapp_desc'      => 'Service via WhatsApp',
    'ob_step11_web_chat'           => 'Web Chat',
    'ob_step11_web_chat_desc'      => 'Chat widget on website',

    // Step 12: Review
    'ob_step12_question'           => 'All good? Review before creating',
    'ob_step12_subtitle'           => 'Confirm your agent\'s information.',

    // Onboarding review labels
    'ob_review_name'               => 'Name',
    'ob_review_company'            => 'Company',
    'ob_review_objective'          => 'Objective',
    'ob_review_style'              => 'Style',
    'ob_review_language'           => 'Language',
    'ob_review_persona'            => 'Persona',
    'ob_review_behavior'           => 'Behavior',
    'ob_review_finish_action'      => 'Closing',
    'ob_review_knowledge'          => 'Knowledge',
    'ob_review_channel'            => 'Channel',
    'ob_review_media'              => 'Media',
    'ob_review_media_files'        => ':count file(s): :names',

    // Toastr (onboarding)
    'ob_toast_name_required'       => 'Give your agent a name.',
    'ob_toast_preparing'           => 'Preparing...',
    'ob_toast_prepare_error'       => 'Error preparing agent.',
    'ob_toast_agent_created'       => 'Agent created successfully!',
    'ob_toast_finalizing'          => 'Finalizing...',
    'ob_toast_file_too_large'      => 'File too large (max 20 MB).',
    'ob_toast_describe_file'       => 'Describe when the agent should send this file.',
    'ob_toast_file_uploaded'       => 'File uploaded!',
    'ob_toast_file_error'          => 'Error uploading file.',
    'ob_toast_remove_confirm'      => 'Remove this file?',
    'ob_toast_sending'             => 'Uploading...',

    // =========================================================================
    // FORM PAGE (agents/form.blade.php) — Edit / Create
    // =========================================================================

    'form_title'                   => 'Artificial Intelligence',
    'form_heading_edit'            => 'Edit Agent',
    'form_heading_create'          => 'New Agent',

    // Channel selector
    'channel_label'                => 'Operating channel',

    // Toggle: active
    'toggle_active_on'             => 'Agent Active',
    'toggle_active_off'            => 'Agent Inactive',
    'toggle_active_desc'           => 'Enable to respond automatically',

    // Toggle: auto-assign
    'toggle_auto_assign_on'        => 'Auto-assign Enabled',
    'toggle_auto_assign_off'       => 'Auto-assign Disabled',
    'toggle_auto_assign_desc'      => 'Automatically assign to new WhatsApp conversations',

    // WhatsApp instances
    'wa_instances_title'           => 'WhatsApp Instances',
    'wa_instances_hint'            => 'Select which numbers this agent handles. If none selected, it handles all.',

    // ── Section 1: Identity ──
    's1_title'                     => '1. Identity',
    's1_name'                      => 'Agent Name *',
    's1_name_placeholder'          => 'E.g.: Sales Assistant',
    's1_company'                   => 'Company Name',
    's1_company_placeholder'       => 'Your Company Inc.',
    's1_objective'                 => 'Objective *',
    's1_objective_sales'           => 'Sales',
    's1_objective_support'         => 'Support',
    's1_objective_general'         => 'General',
    's1_communication'             => 'Communication *',
    's1_style_formal'              => 'Formal',
    's1_style_normal'              => 'Normal',
    's1_style_casual'              => 'Casual',
    's1_language'                  => 'Language *',
    's1_lang_pt'                   => 'Portuguese (BR)',
    's1_lang_en'                   => 'English',
    's1_lang_es'                   => 'Spanish',
    's1_industry'                  => 'Sector / Industry',
    's1_industry_placeholder'      => 'E.g.: E-commerce, SaaS, Healthcare...',

    // ── Section 2: Persona ──
    's2_title'                     => '2. Persona & Behavior',
    's2_persona'                   => 'Persona Description',
    's2_persona_placeholder'       => 'E.g.: You are Maria, a friendly and proactive sales consultant who loves helping customers find the right solution...',
    's2_behavior'                  => 'Behavior',
    's2_behavior_placeholder'      => 'E.g.: Always ask the customer\'s name. Never offer discounts without checking first. Prioritize solving the problem before selling...',

    // ── Section 3: Flow ──
    's3_title'                     => '3. Service Flow',
    's3_on_finish'                 => 'When Finishing Service',
    's3_on_finish_placeholder'     => 'E.g.: Thank the contact, offer a 1-5 star rating and close with a positive message.',
    's3_on_transfer'               => 'When Transferring to Human',
    's3_on_transfer_placeholder'   => 'E.g.: If the customer asks to speak with an agent, apologize for the wait and inform that a human will take over shortly.',
    's3_on_invalid'                => 'On Invalid Message / Jailbreak Attempt',
    's3_on_invalid_placeholder'    => 'E.g.: Inform that you can only help with topics related to our service and offer valid options.',

    // ── Section 4: Conversation stages ──
    's4_title'                     => '4. Conversation Stages',
    's4_description'               => 'Define the stages the agent should follow during the conversation (optional).',
    's4_stage_name_placeholder'    => 'Stage name',
    's4_stage_desc_placeholder'    => 'Description (optional)',
    's4_add_stage'                 => 'Add stage',

    // ── Section 5: Knowledge base ──
    's5_title'                     => '5. Knowledge Base',
    's5_description'               => 'Include information about your company, products, prices, FAQs, policies, etc. The agent will use this to respond.',
    's5_kb_placeholder'            => "Company: XYZ Technology\nProducts: Basic Plan \$49/month, Pro Plan \$99/month\nHours: Mon-Fri 9am-6pm\nPhone: (555) 123-4567\n...",

    // Knowledge files
    's5_files_title'               => 'Knowledge Files',
    's5_files_description'         => 'Upload PDFs, images, or text files. Content will be automatically extracted and used by the agent.',
    's5_dropzone_text'             => 'Click or drag files here',
    's5_dropzone_hint'             => 'PDF, TXT, CSV, PNG, JPG, WEBP — max 20 MB',
    's5_status_extracted'          => 'Extracted',
    's5_status_failed'             => 'Failed',
    's5_status_pending'            => 'Pending',
    's5_preview_btn'               => 'View preview',
    's5_remove_btn'                => 'Remove',

    // Toastr (knowledge files)
    'toast_kb_upload_error'        => 'Error uploading file.',
    'toast_kb_uploading'           => 'Uploading and extracting content from',
    'toast_kb_processed'           => 'File processed successfully!',
    'toast_kb_extract_failed'      => 'Extraction failed. Check the reason in the list.',
    'toast_kb_delete_confirm'      => 'Remove ":name" from the knowledge base?',
    'toast_kb_delete_error'        => 'Error removing file.',
    'toast_kb_deleted'             => 'File removed.',
    'toast_kb_network_error'       => 'Network error. Please try again.',

    // ── Section 5b: Agent media ──
    's5b_title'                    => 'Agent Media',
    's5b_description'              => 'Files the agent can <strong>send to the contact</strong> during conversation (catalogs, photos, PDFs). Different from the Knowledge Base, which is for internal context only.',
    's5b_dropzone_text'            => 'Click or drag files here',
    's5b_dropzone_hint'            => 'PNG, JPG, PDF, DOC — max 20 MB',
    's5b_no_description'           => 'No description',
    's5b_desc_placeholder'         => 'Describe when the agent should send this file',
    's5b_cancel'                   => 'Cancel',
    's5b_upload'                   => 'Upload',
    's5b_uploading'                => 'Uploading...',

    // Toastr (media)
    'toast_media_too_large'        => 'File too large (max 20 MB).',
    'toast_media_describe'         => 'Describe when the agent should send this file.',
    'toast_media_uploaded'         => 'File uploaded!',
    'toast_media_upload_error'     => 'Error uploading.',
    'toast_media_delete_confirm'   => 'Remove ":name"?',
    'toast_media_deleted'          => 'File removed.',
    'toast_media_delete_error'     => 'Error removing.',
    'toast_media_network_error'    => 'Network error.',

    // ── Section 6: Tools ──
    's6_title'                     => '6. Agent Tools',

    // Pipeline tool
    's6_pipeline_on'               => 'Pipeline Control Enabled',
    's6_pipeline_off'              => 'Pipeline Control Disabled',
    's6_pipeline_desc'             => 'The agent can move leads between pipeline stages automatically during service',

    // Tags tool
    's6_tags_on'                   => 'Tag Assignment Enabled',
    's6_tags_off'                  => 'Tag Assignment Disabled',
    's6_tags_desc'                 => 'The agent can automatically add tags to the conversation based on context',

    // Intent notify
    's6_intent_on'                 => 'Intent Detection Enabled',
    's6_intent_off'                => 'Intent Detection Disabled',
    's6_intent_desc'               => 'Notifies when the agent identifies clear signals of purchase intent, scheduling, or closing',

    // Calendar tool
    's6_calendar_on'               => 'Google Calendar Enabled',
    's6_calendar_off'              => 'Google Calendar Disabled',
    's6_calendar_desc'             => 'The agent can create, reschedule, and cancel events on Google Calendar during conversation',
    's6_calendar_select_label'     => 'Google Calendar',
    's6_calendar_primary'          => 'Primary calendar',
    's6_calendar_hint'             => 'Select which calendar the agent will create events in. Calendars are loaded from the connected Google account.',
    's6_calendar_reload'           => 'Reload list',
    's6_calendar_instructions'     => 'How the agent should use the calendar',
    's6_calendar_instructions_ph'  => 'E.g.: When the user asks to schedule a meeting, check existing events and create the event. Meetings are 1 hour by default. Always confirm the time with the user before creating.',
    's6_calendar_integrations'     => 'The agent will receive these instructions in the prompt. Make sure you have connected Google Calendar in',
    's6_calendar_integrations_link' => 'Settings > Integrations',
    's6_calendar_loading'          => 'Loading...',
    's6_calendar_principal'        => '(primary)',

    // Products tool
    's6_products_on'               => 'Product Catalog Enabled',
    's6_products_off'              => 'Product Catalog Disabled',
    's6_products_desc'             => 'The agent queries prices, sends product photos/videos, and links items to the lead automatically',

    // Transfer department
    's6_transfer_department'       => 'Transfer to department',
    's6_transfer_dept_none'        => '— None —',
    's6_transfer_dept_hint'        => 'If set, when transferring to human the conversation will be forwarded to the department (with automatic distribution). Takes priority over the user below.',

    // Transfer user
    's6_transfer_user'             => 'Assign conversation to user (on transfer)',
    's6_transfer_user_none'        => '— None (no automatic assignment) —',
    's6_transfer_user_hint'        => 'Fallback: if no department is set, the conversation will be assigned to this user and AI disabled.',

    // ── Section 7: Advanced ──
    's7_title'                     => '7. Advanced Settings',
    's7_max_message_length'        => 'Max Message Length (characters)',
    's7_response_delay'            => 'Delay between messages (seconds)',
    's7_response_delay_tooltip'    => 'Pause between each part of the response (when split into multiple messages)',
    's7_response_wait'             => 'Batching wait time (seconds)',
    's7_response_wait_tooltip'     => 'Wait X seconds before processing, to group messages sent in sequence. 0 = no wait.',
    's7_response_wait_desc'        => 'When the user sends multiple messages in a row, the agent waits this long before responding, processing them all together.',

    // ── Section 8: Follow-up ──
    's8_title'                     => '8. Automatic Follow-up',
    's8_followup_on'               => 'Follow-up Enabled',
    's8_followup_off'              => 'Follow-up Disabled',
    's8_followup_desc'             => 'When the customer stops responding, the agent automatically re-engages',
    's8_delay_minutes'             => 'Interval between attempts (minutes)',
    's8_delay_default'             => 'Default: 40 minutes',
    's8_max_count'                 => 'Maximum attempts per conversation',
    's8_max_count_hint'            => 'After this limit the conversation is ignored',
    's8_hour_start'                => 'Business hours — start (hour)',
    's8_hour_start_hint'           => 'E.g.: 8 = from 08:00',
    's8_hour_end'                  => 'Business hours — end (hour)',
    's8_hour_end_hint'             => 'E.g.: 18 = until 18:59',

    // ── Section 9: Widget ──
    's9_title'                     => '9. Chat Widget',
    's9_bot_name'                  => 'Bot Name',
    's9_bot_name_placeholder'      => 'E.g.: Virtual Assistant',
    's9_bot_name_hint'             => 'Displayed in the widget header',
    's9_widget_type'               => 'Widget Type',
    's9_widget_bubble'             => 'Bubble',
    's9_widget_inline'             => 'Inline',
    's9_widget_type_hint'          => 'Bubble: floating button. Inline: embedded in the page.',
    's9_avatar'                    => 'Bot Avatar',
    's9_avatar_hint'               => 'Choose an avatar or upload a custom image.',
    's9_welcome_message'           => 'Welcome Message',
    's9_welcome_placeholder'       => 'Hello! How can I help you today?',
    's9_welcome_hint'              => 'Sent automatically when the visitor opens the chat.',
    's9_widget_color'              => 'Widget Color',
    's9_embed_code'                => 'Embed Code',
    's9_embed_hint'                => 'Paste this code into your website\'s HTML to display the chat widget.',
    's9_embed_copy'                => 'Copy',
    's9_embed_copied'              => 'Copied!',

    // Form footer
    'form_save'                    => 'Save changes',
    'form_create'                  => 'Create Agent',
    'form_cancel'                  => 'Cancel',
    'form_test_agent'              => 'Test Agent',

    // Test chat (form page)
    'form_test_title'              => 'Test:',
    'form_test_greeting'           => 'Hello! I\'m :name. How can I help?',
    'form_test_placeholder'        => 'Type a message...',
    'form_test_error'              => 'Error: ',
    'form_test_error_generic'      => 'Failed to get a response.',
    'form_test_error_connection'   => 'Connection error.',

    // =========================================================================
    // CONFIG PAGE (ai/config.blade.php)
    // =========================================================================

    'config_title'                 => 'AI Agent',
    'config_heading'               => 'Artificial Intelligence — Configuration',
    'config_subtitle'              => 'Configure the LLM provider for use with AI agents.',

    'config_provider_title'        => 'LLM Provider',
    'config_provider_subtitle'     => 'Choose which AI service will be used by your agents.',
    'config_provider_label'        => 'Service',

    'config_api_key'               => 'API Key',
    'config_api_key_placeholder'   => 'Enter your API key',
    'config_api_key_hint'          => 'The key is stored securely and never exposed to the browser.',
    'config_show_hide'             => 'Show/hide',

    'config_model'                 => 'Model',

    'config_save'                  => 'Save',
    'config_test'                  => 'Test connection',
    'config_test_ok'               => 'Connection OK',
    'config_test_api_key_warning'  => 'Enter the API key before testing.',
    'config_save_error'            => 'Error saving.',

    'config_next_step_title'       => 'Next step',
    'config_next_step_text'        => 'After configuring the provider, go to',
    'config_next_step_link'        => 'Agents',
    'config_next_step_suffix'      => 'to create your first AI agent.',

    // ── Reminders ───────────────────────────────────────────────────
    'reminder_title'               => 'WhatsApp Reminders',
    'reminder_desc'                => 'When the agent schedules an event, reminders are automatically sent to the lead via WhatsApp before the scheduled time.',
    'reminder_15min'               => '15 minutes before',
    'reminder_30min'               => '30 minutes before',
    'reminder_1h'                  => '1 hour before',
    'reminder_2h'                  => '2 hours before',
    'reminder_12h'                 => '12 hours before',
    'reminder_1d'                  => '1 day before',
    'reminder_2d'                  => '2 days before',
    'reminder_template_label'      => 'Reminder message',
    'reminder_template_ph'         => 'Hi @{{lead_name}}! Reminder: you have @{{event_title}} scheduled for @{{event_date}} at @{{event_time}}.',
    'reminder_template_hint'       => 'Placeholders: @{{lead_name}}, @{{event_title}}, @{{event_date}}, @{{event_time}}, @{{event_location}}. Leave empty to use default template.',
    'index_subtitle'    => 'Configure intelligent agents for your customer service.',

    // =========================================================================
    // WIZARD MULTI-STEP (agents/wizard.blade.php) — Creation
    // =========================================================================
    'wz_step1_title'        => 'Who is your agent?',
    'wz_step1_sub'          => 'Start by choosing an avatar and giving your agent a name.',
    'wz_avatar_label'       => 'Agent avatar',
    'wz_step2_title'        => 'Goal and personality',
    'wz_step2_sub'          => 'How should the agent behave and what does it do?',
    'wz_objective_label'    => 'What is the main goal?',
    'wz_obj_sales_desc'     => 'Qualify leads and help close sales',
    'wz_obj_support_desc'   => 'Answer questions and solve problems',
    'wz_obj_general_desc'   => 'General assistance and routing',
    'wz_style_label'        => 'How should it communicate?',
    'wz_style_formal_desc'  => 'Professional and respectful',
    'wz_style_normal_desc'  => 'Cordial and clear',
    'wz_style_casual_desc'  => 'Relaxed and friendly',
    'wz_step3_title'        => 'Where will the agent operate?',
    'wz_step3_sub'          => 'Pick the channel and configure how it receives conversations.',
    'wz_step4_title'        => 'Knowledge and tools',
    'wz_step4_sub'          => 'What the agent knows and what it can do in the CRM.',
    'wz_kb_hint'            => 'Paste text about your business, products, FAQs. You can add files later.',
    'wz_tools_label'        => 'Tools available to the agent',
    'wz_step5_title'        => 'Follow-up and final tweaks',
    'wz_step5_sub'          => 'The agent can automatically resume silent conversations.',
    'wz_followup_title'     => 'Automatic follow-up enabled',
    'wz_followup_desc'      => 'The agent will resume conversations where the lead stopped replying, within business hours.',
    'wz_advanced_label'     => 'Advanced settings',
    'wz_back'               => 'Back',
    'wz_continue'           => 'Continue',
    'wz_create_agent'       => 'Create agent',
    'wz_name_required'      => 'Give your agent a name to continue.',

    // =========================================================================
    // EDIT FORM SIDEBAR-TABS (agents/form.blade.php refactor)
    // =========================================================================
    'edit_sect_identity'    => 'Identity',
    'edit_sect_persona'     => 'Persona',
    'edit_sect_channel'     => 'Channel',
    'edit_sect_stages'      => 'Stages',
    'edit_sect_kb'          => 'Knowledge',
    'edit_sect_media'       => 'Media',
    'edit_sect_tools'       => 'Tools',
    'edit_sect_followup'    => 'Follow-up',
    'edit_sect_advanced'    => 'Advanced',
    'edit_sect_widget'      => 'Web Widget',
    'edit_avatar_label'     => 'Avatar (internal — not visible to leads)',
    'edit_save'             => 'Save changes',
];
