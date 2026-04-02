<?php

declare(strict_types=1);

return [

    // ── UI Buttons ───────────────────────────────────────────────────
    'btn_next'    => 'Next',
    'btn_prev'    => 'Previous',
    'btn_skip'    => 'Skip tour',
    'btn_done'    => 'Done',
    'reset_tours' => 'Reset all tours',

    // ── Global Tour (Dashboard) ──────────────────────────────────────
    'global_welcome_title' => 'Welcome to Syncro!',
    'global_welcome_desc'  => 'Let\'s take a quick tour of the platform so you can discover the main features.',

    'global_home_title' => 'Dashboard',
    'global_home_desc'  => 'This is your main panel. Track metrics, recent activity and get an overview of your CRM at a glance.',

    'global_chats_title' => 'Chat Inbox',
    'global_chats_desc'  => 'All your WhatsApp, Instagram and Website conversations in one place. Reply to customers without switching tools.',

    'global_crm_title' => 'CRM & Pipeline',
    'global_crm_desc'  => 'Manage your leads on a Kanban board, track the sales funnel and move deals between stages with drag-and-drop.',

    'global_automation_title' => 'Automations',
    'global_automation_desc'  => 'Create automatic rules to notify, move leads and trigger actions when events happen in your CRM.',

    'global_reports_title' => 'Reports',
    'global_reports_desc'  => 'Analyze the performance of your sales, campaigns and team with detailed charts and KPIs.',

    'global_settings_title' => 'Settings',
    'global_settings_desc'  => 'Customize pipelines, custom fields, integrations, users and much more.',

    'global_search_title' => 'Global Search',
    'global_search_desc'  => 'Press Cmd+K (or Ctrl+K) to quickly search leads, conversations and settings from any page.',

    'global_notifications_title' => 'Notifications',
    'global_notifications_desc'  => 'Stay on top of new messages, overdue tasks and important alerts in real time.',

    'global_sophia_title' => 'Sophia — AI Assistant',
    'global_sophia_desc'  => 'Sophia can answer questions, create CRM entities and suggest actions. Click the floating icon to chat with her.',

    'global_metrics_title' => 'Dashboard Metrics',
    'global_metrics_desc'  => 'These cards show your most important numbers: leads, sales, conversions and activities for the period.',

    'global_plan_title' => 'Your Plan',
    'global_plan_desc'  => 'See which plan you are on, resource limits and available upgrade options.',

    'global_done_title' => 'All set!',
    'global_done_desc'  => 'Now you know the main features. Each page also has its own tour — feel free to explore!',

    // ── Kanban ───────────────────────────────────────────────────────
    'kanban_board_title'   => 'Kanban Board',
    'kanban_board_desc'    => 'This is your visual pipeline. Drag cards between columns to move leads through the funnel stages.',

    'kanban_stages_title'  => 'Funnel Stages',
    'kanban_stages_desc'   => 'Each column represents a stage. You can customize names and order in Settings > Pipelines.',

    'kanban_cards_title'   => 'Lead Cards',
    'kanban_cards_desc'    => 'Each card shows the lead\'s name, value and tags. Click to open the full details.',

    'kanban_filters_title' => 'Filters',
    'kanban_filters_desc'  => 'Filter by assignee, tags, source, date and more to focus on the leads that matter.',

    'kanban_import_title'  => 'Import Leads',
    'kanban_import_desc'   => 'Bulk import leads via Excel or CSV spreadsheet directly into the pipeline.',

    // ── Contacts ─────────────────────────────────────────────────────
    'contacts_table_title'      => 'Contacts Table',
    'contacts_table_desc'       => 'All your leads organized in a table with customizable columns and sorting.',

    'contacts_search_title'     => 'Search & Filters',
    'contacts_search_desc'      => 'Search by name, phone, email or any field. Use advanced filters to narrow down results.',

    'contacts_new_lead_title'   => 'New Contact',
    'contacts_new_lead_desc'    => 'Click here to manually create a new lead with all the relevant information.',

    'contacts_duplicates_title' => 'Duplicates',
    'contacts_duplicates_desc'  => 'The system detects duplicate leads automatically. Review and merge them when needed.',

    // ── Lead Profile ─────────────────────────────────────────────────
    'lead_profile_hero_title'     => 'Lead Header',
    'lead_profile_hero_desc'      => 'See the lead\'s name, phone, email and status at a glance.',

    'lead_profile_tabs_title'     => 'Detail Tabs',
    'lead_profile_tabs_desc'      => 'Navigate between timeline, notes, tasks, attachments and conversation history.',

    'lead_profile_actions_title'  => 'Quick Actions',
    'lead_profile_actions_desc'   => 'Send a message, create a task, add a note or register a sale right from here.',

    'lead_profile_pipeline_title' => 'Pipeline Stage',
    'lead_profile_pipeline_desc'  => 'View and change which funnel stage this lead is currently in.',

    'lead_profile_sidebar_title'  => 'Sidebar',
    'lead_profile_sidebar_desc'   => 'Additional information such as custom fields, products, score and lead tags.',

    // ── Tasks ────────────────────────────────────────────────────────
    'tasks_filters_title'  => 'Task Filters',
    'tasks_filters_desc'   => 'Filter by status, priority, type and assignee to quickly find what you need.',

    'tasks_table_title'    => 'Task List',
    'tasks_table_desc'     => 'View all tasks with due date, priority and linked lead. Click to mark as completed.',

    'tasks_new_task_title' => 'New Task',
    'tasks_new_task_desc'  => 'Create tasks such as call, visit, email or meeting and link them to a lead or conversation.',

    // ── Calendar ─────────────────────────────────────────────────────
    'calendar_layout_title'    => 'Calendar',
    'calendar_layout_desc'     => 'View your tasks and events in a monthly, weekly or daily calendar format.',

    'calendar_sidebar_title'   => 'Mini Calendar',
    'calendar_sidebar_desc'    => 'Quickly navigate between dates and see a summary of the day\'s activities.',

    'calendar_new_event_title' => 'New Event',
    'calendar_new_event_desc'  => 'Click any date or use the button to create a new event or task.',

    // ── Lists ────────────────────────────────────────────────────────
    'lists_overview_title' => 'Contact Lists',
    'lists_overview_desc'  => 'Organize your leads into lists for segmentation and bulk actions.',

    'lists_types_title'    => 'List Types',
    'lists_types_desc'     => 'Static lists are manual. Dynamic lists update automatically based on filter criteria.',

    'lists_new_list_title' => 'New List',
    'lists_new_list_desc'  => 'Create a list by choosing the type and the segmentation criteria for your leads.',

    // ── Goals ────────────────────────────────────────────────────────
    'goals_summary_title'  => 'Goals Summary',
    'goals_summary_desc'   => 'Track the overall progress of your team\'s sales goals in a consolidated view.',

    'goals_tabs_title'     => 'Period Tabs',
    'goals_tabs_desc'      => 'Switch between daily, weekly and monthly goals to analyze different time periods.',

    'goals_card_title'     => 'Goal Card',
    'goals_card_desc'      => 'Each card shows the target, current progress and percentage achieved with a visual bar.',

    'goals_new_goal_title' => 'New Goal',
    'goals_new_goal_desc'  => 'Set goals per salesperson with type, target value, period and achievement bonuses.',

    // ── Chats ────────────────────────────────────────────────────────
    'chats_sidebar_title'  => 'Conversation List',
    'chats_sidebar_desc'   => 'All WhatsApp, Instagram and Website conversations appear here, sorted by last message.',

    'chats_messages_title' => 'Message Area',
    'chats_messages_desc'  => 'Send texts, images, audio and documents. Quick messages speed up your responses.',

    'chats_details_title'  => 'Contact Details',
    'chats_details_desc'   => 'View linked lead information, tags, notes and history without leaving the chat.',

    'chats_assign_title'   => 'Assign Conversation',
    'chats_assign_desc'    => 'Assign the conversation to a user, department, AI agent or chatbot flow.',

    // ── Chatbot ──────────────────────────────────────────────────────
    'chatbot_grid_title'   => 'Your Flows',
    'chatbot_grid_desc'    => 'View all your chatbot flows. Each card shows channel, status and total completions.',

    'chatbot_create_title' => 'Create Flow',
    'chatbot_create_desc'  => 'Create a new flow by choosing the channel (WhatsApp, Instagram or Website) and trigger keywords.',

    'chatbot_badges_title' => 'Status & Metrics',
    'chatbot_badges_desc'  => 'Badges show whether the flow is active and how many times it has been completed.',

    // ── AI Agents ────────────────────────────────────────────────────
    'ai_agents_grid_title'  => 'Your Agents',
    'ai_agents_grid_desc'   => 'View all configured AI agents that automatically handle customer conversations.',

    'ai_agents_card_title'  => 'Agent Card',
    'ai_agents_card_desc'   => 'Each card shows the agent\'s objective, communication style and active channels.',

    'ai_agents_media_title' => 'Agent Media',
    'ai_agents_media_desc'  => 'Attach images, PDFs and documents that the agent can send during conversations.',

    'ai_agents_test_title'  => 'Test Agent',
    'ai_agents_test_desc'   => 'Use the test chat to simulate a conversation and fine-tune the agent\'s behavior.',

    // ── Automations ──────────────────────────────────────────────────
    'automations_table_title'    => 'Automations List',
    'automations_table_desc'     => 'View all active automations, their triggers and how many times they have run.',

    'automations_triggers_title' => 'Triggers',
    'automations_triggers_desc'  => 'Each automation fires when an event occurs: new lead, stage change, tag added and more.',

    'automations_new_title'      => 'New Automation',
    'automations_new_desc'       => 'Create a rule by defining the trigger, conditions and actions to execute.',

    // ── Reports ──────────────────────────────────────────────────────
    'reports_filters_title' => 'Report Filters',
    'reports_filters_desc'  => 'Select period, pipeline, assignee and other criteria to generate the report.',

    'reports_kpis_title'    => 'Key KPIs',
    'reports_kpis_desc'     => 'See the key indicators: leads generated, deals closed, conversion rate and average ticket.',

    'reports_charts_title'  => 'Charts',
    'reports_charts_desc'   => 'Analyze trends with line, bar and pie charts by period and assignee.',

    // ── Campaigns ────────────────────────────────────────────────────
    'campaigns_kpis_title'    => 'Campaign KPIs',
    'campaigns_kpis_desc'     => 'Track investment, CPL, CPA and ROI for all campaigns in one place.',

    'campaigns_top_title'     => 'Top Campaigns',
    'campaigns_top_desc'      => 'View the best performing campaigns ranked by leads generated or return.',

    'campaigns_ranking_title' => 'Channel Ranking',
    'campaigns_ranking_desc'  => 'Compare performance across channels like Facebook Ads, Google Ads and organic.',

    // ── NPS ──────────────────────────────────────────────────────────
    'nps_kpis_title'       => 'NPS KPIs',
    'nps_kpis_desc'        => 'Track average score, response rate and distribution across promoters, passives and detractors.',

    'nps_charts_title'     => 'NPS Charts',
    'nps_charts_desc'      => 'See how your NPS evolves over time and identify satisfaction trends.',

    'nps_new_survey_title' => 'New Survey',
    'nps_new_survey_desc'  => 'Create an NPS survey by choosing the delivery channel, trigger and custom question.',

    // ── Profile ──────────────────────────────────────────────────────
    'profile_form_title'   => 'Profile Details',
    'profile_form_desc'    => 'Update your name, email and password. This information is used across the system.',

    'profile_avatar_title' => 'Profile Picture',
    'profile_avatar_desc'  => 'Add a photo to personalize your account. It appears in chats and comments.',

    // ── Integrations ─────────────────────────────────────────────────
    'integrations_cards_title'   => 'Available Integrations',
    'integrations_cards_desc'    => 'Connect WhatsApp, Instagram, Facebook Ads, Google Ads and other platforms.',

    'integrations_connect_title' => 'Connect',
    'integrations_connect_desc'  => 'Click an integration to start the OAuth authorization process.',

    'integrations_status_title'  => 'Connection Status',
    'integrations_status_desc'   => 'Check whether the integration is active, has errors or needs reconnection.',

    // ── Pipelines ────────────────────────────────────────────────────
    'pipelines_list_title'    => 'Your Pipelines',
    'pipelines_list_desc'     => 'Manage your team\'s sales funnels. Each pipeline has its own set of stages.',

    'pipelines_stages_title'  => 'Stages',
    'pipelines_stages_desc'   => 'Add, reorder and edit the stages of each pipeline with drag-and-drop.',

    'pipelines_outcome_title' => 'Outcome Stages',
    'pipelines_outcome_desc'  => 'Mark stages as "Won" or "Lost" so the system automatically records sales and losses.',

    // ── Scoring ──────────────────────────────────────────────────────
    'scoring_table_title'    => 'Scoring Rules',
    'scoring_table_desc'     => 'View all active lead scoring rules and the points assigned to each event.',

    'scoring_new_rule_title' => 'New Rule',
    'scoring_new_rule_desc'  => 'Create a rule by defining the event, conditions and number of points to assign.',

    // ── Billing ──────────────────────────────────────────────────────
    'billing_plan_title'    => 'Current Plan',
    'billing_plan_desc'     => 'View your plan details, resource limits and renewal date.',

    'billing_upgrade_title' => 'Upgrade',
    'billing_upgrade_desc'  => 'Compare plans and upgrade to unlock more leads, AI agents and features.',

    // ── Partner Dashboard ────────────────────────────────────────────
    'partner_dashboard_rank_title'     => 'Your Rank',
    'partner_dashboard_rank_desc'      => 'See your position in the partner program and what it takes to reach the next level.',

    'partner_dashboard_kpis_title'     => 'Your Numbers',
    'partner_dashboard_kpis_desc'      => 'Track accumulated commissions, active clients and available balance for withdrawal.',

    'partner_dashboard_code_title'     => 'Referral Code',
    'partner_dashboard_code_desc'      => 'Share your code with clients. Each sign-up generates recurring commissions for you.',

    'partner_dashboard_clients_title'  => 'Referred Clients',
    'partner_dashboard_clients_desc'   => 'View all clients who signed up using your code and each one\'s status.',

    'partner_dashboard_withdraw_title' => 'Request Withdrawal',
    'partner_dashboard_withdraw_desc'  => 'When you have available balance, request a PIX withdrawal right here.',

    // ── Partner Resources ────────────────────────────────────────────
    'partner_resources_grid_title'    => 'Support Materials',
    'partner_resources_grid_desc'     => 'Access presentations, manuals and marketing materials to help with sales.',

    'partner_resources_filters_title' => 'Filter Resources',
    'partner_resources_filters_desc'  => 'Use categories to quickly find the material you need.',

    // ── Partner Courses ──────────────────────────────────────────────
    'partner_courses_grid_title'        => 'Available Courses',
    'partner_courses_grid_desc'         => 'Learn about the platform and sales techniques with our exclusive courses.',

    'partner_courses_progress_title'    => 'Your Progress',
    'partner_courses_progress_desc'     => 'Track how many lessons you have completed in each course.',

    'partner_courses_certificate_title' => 'Certificate',
    'partner_courses_certificate_desc'  => 'Complete all lessons to receive a verifiable digital certificate.',

    // ── Products ─────────────────────────────────────────────────────
    'products_table_title' => 'Product Catalog',
    'products_table_desc'  => 'Manage your products and services with pricing, SKU and categories.',
    'products_new_title'   => 'New Product',
    'products_new_desc'    => 'Add a product with photos, price and description.',

    // ── Custom Fields ────────────────────────────────────────────────
    'custom_fields_table_title' => 'Custom Fields',
    'custom_fields_table_desc'  => 'Create extra fields for your leads: text, number, date, select and more.',
    'custom_fields_new_title'   => 'New Field',
    'custom_fields_new_desc'    => 'Define the type, name and options for the custom field.',

    // ── Loss Reasons ─────────────────────────────────────────────────
    'lost_reasons_table_title' => 'Loss Reasons',
    'lost_reasons_table_desc'  => 'Categorize why deals were lost to improve your strategy.',
    'lost_reasons_new_title'   => 'New Reason',
    'lost_reasons_new_desc'    => 'Add a loss reason to use when closing deals.',

    // ── Tags ─────────────────────────────────────────────────────────
    'tags_table_title' => 'Tags',
    'tags_table_desc'  => 'Organize conversations and leads with colored tags.',
    'tags_new_title'   => 'New Tag',
    'tags_new_desc'    => 'Create a tag with name and color for categorization.',

    // ── Sequences ────────────────────────────────────────────────────
    'sequences_table_title' => 'Nurture Sequences',
    'sequences_table_desc'  => 'Automated message sequences to nurture leads over time.',
    'sequences_new_title'   => 'New Sequence',
    'sequences_new_desc'    => 'Create a sequence with steps, delays and automatic messages.',

    // ── Notifications ────────────────────────────────────────────────
    'notifications_prefs_title'   => 'Notification Preferences',
    'notifications_prefs_desc'    => 'Choose which events trigger notifications and through which channel.',
    'notifications_toggles_title' => 'Enable/Disable',
    'notifications_toggles_desc'  => 'Toggle notifications by type: browser, push and sound.',

    // ── Users ────────────────────────────────────────────────────────
    'users_table_title' => 'Team',
    'users_table_desc'  => 'Manage your team members and their roles (admin, manager, viewer).',
    'users_new_title'   => 'New User',
    'users_new_desc'    => 'Invite a team member by email.',

    // ── Departments ──────────────────────────────────────────────────
    'departments_table_title' => 'Departments',
    'departments_table_desc'  => 'Organize your team by sectors with automatic assignment strategy.',
    'departments_new_title'   => 'New Department',
    'departments_new_desc'    => 'Create a department and set the distribution rule (round-robin or least busy).',

    // ── API Keys ─────────────────────────────────────────────────────
    'api_keys_table_title' => 'API Keys',
    'api_keys_table_desc'  => 'Manage API keys to integrate external systems with Syncro.',
    'api_keys_new_title'   => 'New Key',
    'api_keys_new_desc'    => 'Generate an API key with specific permissions.',

    // ── PWA ──────────────────────────────────────────────────────────
    'pwa_install_title' => 'Install App',
    'pwa_install_desc'  => 'Install Syncro as an app on your device for quick access.',

    // ── Audit Log ────────────────────────────────────────────────────
    'audit_table_title'   => 'Audit Log',
    'audit_table_desc'    => 'History of all actions taken in the workspace.',
    'audit_filters_title' => 'Filters',
    'audit_filters_desc'  => 'Filter by user, action, entity and time period.',

    // ── IG Automations ───────────────────────────────────────────────
    'ig_automations_table_title' => 'Instagram Automations',
    'ig_automations_table_desc'  => 'Automatic rules to reply to comments and send DMs.',
    'ig_automations_new_title'   => 'New IG Automation',
    'ig_automations_new_desc'    => 'Create a rule per post, reel or story with keywords.',

];
