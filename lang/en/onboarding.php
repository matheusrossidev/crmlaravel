<?php

return [
    // ── Page ──
    'page_title'        => 'Welcome — Syncro',
    'skip_button'       => 'Skip',
    'skip_button_title' => 'Skip setup',
    'continue'          => 'Continue',
    'back'              => 'Back',

    // ── Progress ──
    'step_1_of_6' => 'Step 1 of 6',
    'step_2_of_6' => 'Step 2 of 6',
    'step_3_of_6' => 'Step 3 of 6',
    'step_4_of_6' => 'Step 4 of 6',
    'step_5_of_6' => 'Step 5 of 6',
    'step_6_of_6' => 'Step 6 of 6',

    // ── Step 3 (NEW): Template picker + sales process ──
    'step3_title'              => 'How do you want to start?',
    'step3_subtitle'           => 'Pick a ready-made template for your industry or let AI build it from scratch.',
    'template_picker_label'    => 'Templates for this industry',
    'template_picker_no_match' => "We don't have specific templates for this industry. AI will build from scratch.",
    'template_card_stages'     => ':count stages',
    'template_use_ai'          => 'Let AI build from scratch',
    'template_use_ai_desc'     => 'AI generates a custom pipeline based on your business.',
    'sales_process_label'      => 'How does your sales process work today? (optional)',
    'sales_process_ph'         => 'E.g.: I get leads on Instagram, qualify them on WhatsApp, schedule a call and send a proposal...',
    'sales_process_hint'       => 'The more details you give, the more personalized the AI output will be.',

    // ── Step 1: Company ──
    'step1_title'              => "Let's set up your company",
    'step1_subtitle'           => 'Start with the basics to personalize your CRM.',
    'company_name_label'       => 'Company name *',
    'company_name_placeholder' => 'E.g.: Silva Real Estate',
    'niche_label'              => 'Industry *',
    'logo_label'               => 'Company logo',
    'logo_optional'            => '(optional)',
    'upload_click'             => 'Click to upload',
    'upload_drag'              => 'or drag here',
    'upload_hint'              => 'PNG, JPG up to 10 MB',

    // Niches
    'niche_imobiliaria'      => 'Real Estate',
    'niche_imobiliaria_desc' => 'Agents and brokers',
    'niche_saude'            => 'Healthcare',
    'niche_saude_desc'       => 'Clinics and offices',
    'niche_advocacia'        => 'Legal',
    'niche_advocacia_desc'   => 'Law firms',
    'niche_ecommerce'        => 'E-commerce',
    'niche_ecommerce_desc'   => 'Online stores',
    'niche_saas'             => 'SaaS / Tech',
    'niche_saas_desc'        => 'Startups and software',
    'niche_outro'            => 'Other',
    'niche_outro_desc'       => 'Any other industry',

    // ── Step 2: Channels ──
    'step2_title'    => 'How do leads reach you?',
    'step2_subtitle' => 'Select all channels you use to capture customers.',

    'channel_whatsapp'     => 'WhatsApp',
    'channel_instagram'    => 'Instagram',
    'channel_facebook_ads' => 'Facebook Ads',
    'channel_google_ads'   => 'Google Ads',
    'channel_site'         => 'Website / Landing Page',
    'channel_indicacao'    => 'Referral',

    'channel_warning_no_whatsapp' => 'The Chat Inbox is unavailable without a WhatsApp connection. You can connect later in Settings.',

    // ── Step 3: Sales process ──
    'step3_title'       => 'How does your sales process work?',
    'step3_subtitle'    => 'Describe or pick a template. AI will create your custom pipeline.',
    'sales_textarea_ph' => 'E.g.: Lead → Qualification → Proposal → Closing',
    'sales_or_choose'   => 'or pick a template:',
    'sales_ai_hint'     => 'AI will create your custom pipeline based on this.',

    // Presets
    'preset_imobiliaria' => 'Lead → Visit → Negotiation → Contract signed',
    'preset_saude'       => 'Contact → Appointment → Quote → Treatment',
    'preset_advocacia'   => 'Consultation → Analysis → Proposal → Contract',
    'preset_ecommerce'   => 'Interest → Cart → Order → Delivery',
    'preset_saas'        => 'Lead → Demo → Trial → Subscription',
    'preset_outro'       => 'Lead → Contact → Proposal → Closing',

    // ── Step 4: Difficulty ──
    'step4_title'    => "What's your biggest challenge?",
    'step4_subtitle' => 'This defines which automations and sequences AI will prioritize.',

    'difficulty_followup'  => 'I forget to follow up with leads',
    'difficulty_disappear' => 'Leads disappear after first contact',
    'difficulty_priority'  => "I don't know which leads to prioritize",
    'difficulty_slow'      => 'I take too long to respond to new leads',
    'difficulty_team'      => "I can't track my sales team",

    // ── Step 5: Team ──
    'step5_title'    => 'How big is your team?',
    'step5_subtitle' => 'This helps configure departments and lead assignment.',

    'team_solo'  => 'Just me',
    'team_small' => '2 to 5',
    'team_mid'   => '6 to 15',
    'team_large' => '15+',

    'summary_title' => 'Based on your answers, we will generate:',
    'summary_items' => 'Custom pipeline, 2 nurture sequences, 3 automations, 5 scoring rules, configured AI agent and 8 quick messages.',

    'generate_button' => 'Generate my CRM with AI',

    // ── Skip modal ──
    'skip_modal_title'   => 'Skip setup?',
    'skip_modal_body'    => 'You can configure everything manually later in <strong>Settings</strong>. AI will not generate anything automatically.',
    'skip_modal_note'    => 'This screen will not appear again.',
    'skip_modal_cancel'  => 'Go back',
    'skip_modal_confirm' => 'Yes, skip',

    // ── Right panel tips ──
    'tip_step1' => 'Over 1,000 companies have set up their CRM with Syncro.',
    'tip_step2' => 'Companies that integrate WhatsApp respond to leads 5x faster.',
    'tip_step3' => 'A well-defined pipeline increases conversion by up to 30%.',
    'tip_step4' => 'Smart automations eliminate 80% of repetitive tasks.',
    'tip_step5' => 'Teams with organized CRM sell 29% more than those without.',

    // ── Errors ──
    'error_company_name'   => 'Enter the company name.',
    'error_select_niche'   => 'Select an industry.',
    'error_select_channel' => 'Select at least one channel.',
    'error_sales_process'  => 'Describe or pick a sales process.',
    'error_difficulty'     => 'Select your biggest challenge.',
    'error_team_size'      => 'Select your team size.',
    'error_server'         => 'Server error. Try again.',
    'error_connection'     => 'Connection error. Check your internet.',

    // ── Loading page ──
    'loading_badge'    => 'Finalizing',
    'result_badge'     => 'Ready',
    'loading_title'    => 'Preparing your CRM...',
    'loading_subtitle' => 'We are setting everything up based on your answers.',

    'loading_step_1' => 'Analyzing your business',
    'loading_step_2' => 'Creating custom pipeline',
    'loading_step_3' => 'Writing nurture sequences',
    'loading_step_4' => 'Setting up follow-up automations',
    'loading_step_5' => 'Defining lead scoring rules',
    'loading_step_6' => 'Setting up automatic support',
    'loading_step_7' => 'Creating quick messages',
    'loading_step_8' => 'Finalizing settings',

    'stat_1' => 'Companies using CRM increase sales by 29%.',
    'stat_2' => 'Automated follow-up reduces lead loss by 45%.',
    'stat_3' => 'Lead scoring increases team efficiency by 30%.',
    'stat_4' => 'Responses under 5 minutes convert 21x more.',

    // ── Result page ──
    'result_title'      => 'All set!',
    'result_title_text' => 'Your CRM is ready!',
    'result_subtitle'   => 'Everything has been set up with our algorithm. Now it\'s your turn.',
    'result_go_crm'     => 'Go to CRM',
    'result_hint'       => 'You can adjust all of this later in Settings.',

    // Checklist
    'checklist_title'       => 'Where to start',
    'check_account'         => 'Account created',
    'check_crm_configured'  => 'CRM configured',
    'check_automations'     => 'Automations and sequences activated',
    'check_whatsapp'        => 'Connect WhatsApp',
    'check_first_lead'      => 'Add your first lead',
    'check_first_message'   => 'Send first message',
    'check_invite'          => 'Invite a teammate',
    'check_import'          => 'Import lead list',

    'action_connect'   => 'Connect',
    'action_add'       => 'Add',
    'action_open_chat' => 'Open chat',
    'action_invite'    => 'Invite',
    'action_import'    => 'Import',

    // ── Result page — real generation results ──
    'result_section_title'       => 'What was generated for you',
    'result_pipeline_created'    => '<strong>:name</strong> with :count stages',
    'result_sequences_created'   => ':count nurture sequence created|:count nurture sequences created',
    'result_automations_created' => ':count active automation|:count active automations',
    'result_scoring_created'     => ':count scoring rule|:count scoring rules',
    'result_ai_agent_created'    => 'AI Agent <strong>:name</strong> configured',
    'result_quick_msgs_created'  => ':count quick message|:count quick messages',
    'result_tags_created'        => ':count tags created',
    'result_reasons_created'     => ':count loss reasons',
    'result_nothing'             => 'No :item generated',
    'result_template_used'       => 'Pipeline created from template <strong>:name</strong>',
    'result_fallback_used'       => "AI didn't respond, so we used ready-made templates based on your industry.",

    // Error states
    'ai_failed_title'   => "AI couldn't generate everything",
    'ai_failed_desc'    => 'Some items were created with default templates. You can try again or adjust manually later.',
    'ai_error_label'    => 'Technical error:',
    'retry_btn'         => 'Try again',
    'retry_btn_loading' => 'Trying...',
];
