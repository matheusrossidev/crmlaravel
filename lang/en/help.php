<?php

declare(strict_types=1);

return [
    'greeting' => 'Hi! I\'m the Syncro assistant. How can I help you?',
    'placeholder' => 'Type your question here...',
    'no_match' => 'Hmm, I couldn\'t find anything about that. Try rephrasing your question or browse the categories below.',
    'contact_support' => 'Need more help? Contact our support at support@syncro.chat',
    'quick_actions' => [
        'How to create a lead?',
        'How to connect WhatsApp?',
        'How to create a chatbot?',
        'How to set up an AI agent?',
        'How to import contacts?',
        'How to create an automation?',
        'How to create an NPS survey?',
        'How to set sales goals?',
    ],
    'sections' => [

        // =====================================================================
        // 1. GETTING STARTED
        // =====================================================================
        'getting_started' => [
            'title' => 'Getting Started',
            'articles' => [
                [
                    'question' => 'What is Syncro?',
                    'keywords' => ['syncro', 'platform', 'what is', 'about', 'crm', 'what does it do', 'features', 'system', 'overview'],
                    'answer' => 'Syncro is an all-in-one CRM and marketing platform that brings together sales pipeline, unified chat inbox (WhatsApp, Instagram, and website), AI agents, chatbots, campaigns, and automations in a single place. It is designed for sales and support teams to manage their leads and conversations efficiently.',
                ],
                [
                    'question' => 'How to get started after creating my account?',
                    'keywords' => ['get started', 'first steps', 'first login', 'setup', 'new account', 'getting started', 'onboarding', 'initial setup'],
                    'answer' => 'After creating your account, we recommend: 1) Connect your WhatsApp in Settings > Integrations. 2) Set up your sales pipeline in Settings > Pipelines. 3) Add your team in Settings > Users. 4) Create your first leads manually or import a spreadsheet in CRM > Contacts.',
                ],
                [
                    'question' => 'What are the main features?',
                    'keywords' => ['features', 'capabilities', 'what can it do', 'modules', 'tools', 'functionality', 'main features'],
                    'answer' => 'The main features are: Kanban Pipeline for sales management, Unified Chat Inbox (WhatsApp + Instagram + Website), AI Agents with memory, Visual Chatbot Builder, Trigger-based Automations, Campaigns with UTM tracking, Reports and Dashboards, and Calendar integrated with Google Calendar.',
                ],
            ],
        ],

        // =====================================================================
        // 2. LEADS & CONTACTS
        // =====================================================================
        'leads' => [
            'title' => 'Leads & Contacts',
            'articles' => [
                [
                    'question' => 'How to create a lead?',
                    'keywords' => ['create lead', 'new lead', 'add lead', 'register lead', 'new contact', 'add contact', 'register contact', 'create contact'],
                    'answer' => 'Go to CRM > Contacts and click the "New Lead" button. Fill in the details such as name, phone, email, and company. You can also assign the lead to a specific pipeline and stage. After saving, the lead will appear both in the contacts list and on the selected pipeline\'s Kanban board.',
                ],
                [
                    'question' => 'How to import leads from Excel or CSV?',
                    'keywords' => ['import', 'excel', 'csv', 'spreadsheet', 'upload', 'import contacts', 'import leads', 'bulk', 'batch', 'mass import'],
                    'answer' => 'Go to CRM > Contacts and click "Import". Upload an Excel (.xlsx) or CSV file. The system will automatically map the columns — review the mapping and confirm. Duplicate leads (same phone or email) will be updated instead of duplicated.',
                ],
                [
                    'question' => 'How to export leads?',
                    'keywords' => ['export', 'download', 'excel', 'csv', 'export contacts', 'export leads', 'spreadsheet', 'extract data'],
                    'answer' => 'In CRM > Contacts, click the "Export" button. The system will generate an Excel file with all filtered leads. If you have applied filters (by tag, stage, date, etc.), only the visible leads will be exported.',
                ],
                [
                    'question' => 'How to edit or delete a lead?',
                    'keywords' => ['edit lead', 'modify lead', 'update lead', 'delete lead', 'remove lead', 'edit contact', 'delete contact', 'change lead'],
                    'answer' => 'Click on the lead in the contacts list or on the Kanban to open the side panel. There you can edit any field by clicking on it. To delete, click the delete button (trash icon) inside the lead panel. Warning: deletion is permanent.',
                ],
                [
                    'question' => 'What are custom fields and how to use them?',
                    'keywords' => ['custom fields', 'extra fields', 'custom field', 'additional fields', 'personalized fields', 'field types', 'custom data'],
                    'answer' => 'Custom fields let you add business-specific information to leads (e.g., tax ID, date of birth, plan of interest). Configure them in Settings > Custom Fields. Ten types are supported: text, number, currency, date, select, multi-select, checkbox, URL, phone, and email. Once created, the fields automatically appear in the lead form.',
                ],
                [
                    'question' => 'How to add tags to a lead?',
                    'keywords' => ['tags', 'labels', 'tag', 'label', 'add tag', 'mark lead', 'classify', 'categorize', 'tagging'],
                    'answer' => 'Open the lead panel by clicking on it and use the tags field to add or remove labels. Tags help classify and filter your leads. You can manage available tags in Settings > Tags. Tags can also be added automatically by chatbots and AI agents.',
                ],
                [
                    'question' => 'How to add notes to a lead?',
                    'keywords' => ['notes', 'note', 'annotation', 'comment', 'observation', 'add note', 'record note', 'lead notes'],
                    'answer' => 'Open the lead panel and go to the "Notes" tab. Click "New Note", type your text, and save. All notes are recorded with date, time, and author. Notes can also be created automatically by AI agents during conversations.',
                ],
            ],
        ],

        // =====================================================================
        // 3. CRM PIPELINE
        // =====================================================================
        'pipeline' => [
            'title' => 'Pipeline & Kanban',
            'articles' => [
                [
                    'question' => 'How does the Kanban pipeline work?',
                    'keywords' => ['kanban', 'pipeline', 'funnel', 'sales funnel', 'stages', 'board', 'how pipeline works', 'sales pipeline'],
                    'answer' => 'The Kanban pipeline displays your leads as cards organized in columns (stages). Each column represents a phase of the sales process (e.g., New, Qualified, Proposal, Closed). You can drag and drop cards between stages to update progress. Access it in CRM > Pipeline.',
                ],
                [
                    'question' => 'How to move a lead between stages?',
                    'keywords' => ['move lead', 'drag', 'change stage', 'switch stage', 'move stage', 'drag and drop', 'update stage'],
                    'answer' => 'On the Kanban board (CRM > Pipeline), simply drag the lead card and drop it on the new stage column. You can also change the stage by opening the lead panel and selecting the new stage in the "Stage" field. The change is automatically recorded in the lead\'s history.',
                ],
                [
                    'question' => 'How to create or edit pipelines and stages?',
                    'keywords' => ['create pipeline', 'new pipeline', 'edit pipeline', 'create stage', 'new stage', 'edit stage', 'configure pipeline', 'configure stages', 'customize funnel'],
                    'answer' => 'Go to Settings > Pipelines. Click "New Pipeline" to create one or the edit icon to modify an existing one. Inside each pipeline, you can add, rename, reorder, and delete stages. Drag stages to change their order. Each pipeline can have its own set of stages.',
                ],
                [
                    'question' => 'How to mark a deal as won or lost?',
                    'keywords' => ['deal won', 'deal lost', 'win', 'lose', 'close deal', 'won', 'lost', 'mark won', 'mark lost', 'close sale'],
                    'answer' => 'Drag the lead to the stage marked as "Won" or "Lost" on the Kanban. You can also open the lead and click the "Mark as Won" or "Mark as Lost" buttons. When marking as lost, the system will ask for the loss reason for future analysis.',
                ],
                [
                    'question' => 'What are loss reasons?',
                    'keywords' => ['loss reasons', 'reason lost', 'why lost', 'lost reason', 'loss reason', 'reasons', 'decline reasons'],
                    'answer' => 'Loss reasons are predefined categories that explain why a deal was not closed (e.g., price too high, went to competitor, no response). Configure them in Settings > Loss Reasons. When a lead is marked as lost, the salesperson selects the reason. This generates valuable data for analysis in reports.',
                ],
            ],
        ],

        // =====================================================================
        // 4. WHATSAPP
        // =====================================================================
        'whatsapp' => [
            'title' => 'WhatsApp',
            'articles' => [
                [
                    'question' => 'How to connect WhatsApp?',
                    'keywords' => ['connect whatsapp', 'whatsapp', 'integrate whatsapp', 'qr code', 'link whatsapp', 'setup whatsapp', 'whatsapp instance', 'pair whatsapp'],
                    'answer' => 'Go to Settings > Integrations and click "WhatsApp". Click "Connect Instance" and scan the QR Code with your phone (WhatsApp > Linked Devices > Link a Device). After scanning, the connection will be established automatically and conversations will start arriving in your inbox.',
                ],
                [
                    'question' => 'How to send messages on WhatsApp?',
                    'keywords' => ['send message', 'send whatsapp', 'reply whatsapp', 'chat whatsapp', 'whatsapp message', 'write message', 'respond'],
                    'answer' => 'Go to Chats > WhatsApp and select a conversation. Type your message in the text box and press Enter or click Send. You can also send images, documents, and audio. To start a new conversation, click "New Message" and enter the contact\'s phone number.',
                ],
                [
                    'question' => 'How to assign conversations to users or departments?',
                    'keywords' => ['assign conversation', 'transfer conversation', 'department', 'assign user', 'transfer chat', 'distribute conversation', 'forward', 'route conversation'],
                    'answer' => 'Inside a conversation in the inbox, click the assignment icon at the top. You can assign it to a specific user or to a department. Departments can have automatic distribution strategies (round-robin or least busy), configurable in Settings > Departments.',
                ],
                [
                    'question' => 'How to import WhatsApp history?',
                    'keywords' => ['import history', 'whatsapp history', 'old messages', 'old conversations', 'import messages', 'conversation history', 'past messages'],
                    'answer' => 'After connecting WhatsApp, go to Settings > Integrations > WhatsApp and click "Import History". The system will fetch recent conversations and messages from WAHA. This process may take a few minutes depending on the volume. You will be notified when the import is complete.',
                ],
                [
                    'question' => 'What is the WhatsApp button widget?',
                    'keywords' => ['whatsapp widget', 'whatsapp button', 'floating button', 'widget', 'site button', 'whatsapp on website', 'chat widget', 'click to chat'],
                    'answer' => 'The widget is a floating WhatsApp button that you can add to your website. When a visitor clicks it, it opens a direct WhatsApp conversation with your number. Configure the widget in Settings > Integrations > WhatsApp Widget, customize the initial message, and copy the code to embed on your site.',
                ],
            ],
        ],

        // =====================================================================
        // 5. INSTAGRAM
        // =====================================================================
        'instagram' => [
            'title' => 'Instagram',
            'articles' => [
                [
                    'question' => 'How to connect Instagram?',
                    'keywords' => ['connect instagram', 'instagram', 'integrate instagram', 'link instagram', 'setup instagram', 'facebook instagram', 'instagram business'],
                    'answer' => 'Go to Settings > Integrations and click "Instagram". You will be redirected to Facebook to authorize access. You need an Instagram Business account linked to a Facebook Page. After authorizing, Instagram DMs will appear in the chat inbox.',
                ],
                [
                    'question' => 'How do Instagram automations work?',
                    'keywords' => ['instagram automation', 'auto reply instagram', 'automatic response instagram', 'instagram comments', 'automatic dm', 'comment automation', 'instagram auto'],
                    'answer' => 'Instagram automations let you automatically respond to comments on specific posts. You define trigger keywords — when someone comments with those words, the system can: reply publicly to the comment, send a private DM, or both. This is great for campaigns like "comment WANT to receive the link".',
                ],
                [
                    'question' => 'How to create an Instagram automation?',
                    'keywords' => ['create instagram automation', 'new instagram automation', 'configure instagram automation', 'setup instagram automation', 'instagram rule'],
                    'answer' => 'Go to Settings > Instagram Automations and click "New Automation". Select the post, define the trigger keywords, and configure the comment reply and/or DM message. You can have multiple active automations simultaneously for different posts and campaigns.',
                ],
            ],
        ],

        // =====================================================================
        // 6. CHATBOT
        // =====================================================================
        'chatbot' => [
            'title' => 'Chatbot',
            'articles' => [
                [
                    'question' => 'How to create a chatbot flow?',
                    'keywords' => ['create chatbot', 'new chatbot', 'create flow', 'new flow', 'chatbot', 'bot', 'automatic flow', 'chatbot builder', 'build chatbot'],
                    'answer' => 'Go to Chatbot > Flows and click "New Flow". Choose the channel (WhatsApp, Instagram, or Website). In the visual builder, drag nodes from the side panel onto the workspace. Connect nodes to define the conversation flow. Start with a message node, then add questions and actions as needed.',
                ],
                [
                    'question' => 'What node types are available in the chatbot?',
                    'keywords' => ['node types', 'block types', 'message', 'question', 'condition', 'action', 'delay', 'end', 'chatbot blocks', 'chatbot nodes'],
                    'answer' => 'The available types are: Message (sends text or image), Question (asks a question with response options), Condition (evaluates a variable to direct the flow), Action (executes actions like change stage, add tag, transfer to human, or send webhook), Delay (pauses for N seconds), and End (final message that closes the flow).',
                ],
                [
                    'question' => 'How to use chatbot variables?',
                    'keywords' => ['chatbot variables', 'variables', 'variable', 'chatbot data', 'capture data', 'interpolation', 'template', 'dynamic content'],
                    'answer' => 'Variables let you capture and reuse user information during the flow. When you create a Question node, the response is saved in a variable (e.g., {{name}}). Use {{variable_name}} in any message to insert the captured value. Variables are saved throughout the entire chatbot session.',
                ],
                [
                    'question' => 'How to embed the chatbot on my website?',
                    'keywords' => ['embed chatbot', 'chatbot website', 'chatbot widget', 'install chatbot', 'chatbot code', 'chatbot script', 'website chatbot', 'chatbot on site'],
                    'answer' => 'Create a flow with the "Website" channel. After saving, go to the flow settings and copy the embed code (JavaScript snippet). Paste this code before the closing </body> tag in your website\'s HTML. The chat widget will automatically appear for your visitors.',
                ],
                [
                    'question' => 'How to test a chatbot?',
                    'keywords' => ['test chatbot', 'chatbot test', 'preview chatbot', 'simulate chatbot', 'verify chatbot', 'debug chatbot', 'try chatbot'],
                    'answer' => 'In the chatbot builder, click the "Test" button to open the simulator. You can interact with the flow as if you were an end user. To test on WhatsApp, assign the flow to a test conversation and send a message with the flow\'s trigger keyword.',
                ],
            ],
        ],

        // =====================================================================
        // 7. AI AGENTS
        // =====================================================================
        'ai_agent' => [
            'title' => 'AI Agents',
            'articles' => [
                [
                    'question' => 'How to create an AI agent?',
                    'keywords' => ['create agent', 'new agent', 'ai agent', 'artificial intelligence', 'ai', 'create ai', 'configure ai', 'setup ai', 'ai bot'],
                    'answer' => 'Go to AI > Agents and click "New Agent". Define the name, objective, communication style, and persona. Configure the knowledge base with information about your business. After saving, you can assign the agent to WhatsApp or Instagram conversations so it responds automatically.',
                ],
                [
                    'question' => 'How to configure the AI agent\'s knowledge base?',
                    'keywords' => ['knowledge base', 'ai knowledge', 'train ai', 'teach ai', 'ai documents', 'ai files', 'agent information', 'ai training'],
                    'answer' => 'In the agent editor, go to the "Knowledge Base" section. You can type information directly in the text field (FAQ, rules, business data) and also upload files (PDF, TXT, DOCX). The agent will use all this content to answer questions accurately and contextually.',
                ],
                [
                    'question' => 'What are AI agent tools?',
                    'keywords' => ['ai tools', 'agent tools', 'pipeline tool', 'tags tool', 'calendar tool', 'intent', 'agent capabilities', 'ai features'],
                    'answer' => 'Agents have optional tools: Pipeline (automatically moves leads between stages), Tags (adds/removes labels), Intent Detection (alerts the team about buying signals), Calendar (checks/creates appointments in Google Calendar), and Voice Reply (sends audio messages). Enable each tool in the agent editor.',
                ],
                [
                    'question' => 'How does automatic follow-up work?',
                    'keywords' => ['follow up', 'followup', 'follow-up', 'recontact', 'automatic message', 'reminder', 'ai follow-up', 're-engage'],
                    'answer' => 'Automatic follow-up allows the AI agent to re-contact customers who haven\'t responded after a defined period. Configure the interval (e.g., 24 hours) and maximum number of attempts in the agent editor. The agent will send personalized messages trying to resume the conversation naturally.',
                ],
                [
                    'question' => 'How to test an AI agent?',
                    'keywords' => ['test agent', 'test ai', 'ai test', 'test chat', 'simulate ai', 'chat with ai', 'preview ai', 'try agent'],
                    'answer' => 'On the agent page, click "Test Chat". A conversation window will open for you to interact directly with the agent using its current settings. This lets you validate responses, communication tone, and tool usage before putting the agent into production.',
                ],
                [
                    'question' => 'What are AI tokens and how does billing work?',
                    'keywords' => ['tokens', 'ai credits', 'ai billing', 'ai limit', 'quota', 'tokens exhausted', 'buy tokens', 'token package', 'ai pricing', 'ai cost'],
                    'answer' => 'Tokens are the consumption unit for AI models (each message sent and received consumes tokens). Your plan includes a monthly token allowance. When the limit is reached, agents pause until the next billing cycle or until you purchase an additional package. Check your usage in AI > Agents and buy extra packages on the same page.',
                ],
            ],
        ],

        // =====================================================================
        // 8. CAMPAIGNS & REPORTS
        // =====================================================================
        'campaigns' => [
            'title' => 'Campaigns & Reports',
            'articles' => [
                [
                    'question' => 'How to track campaigns with UTM?',
                    'keywords' => ['utm', 'track campaign', 'tracking', 'utm_source', 'utm_medium', 'utm_campaign', 'campaign tracking', 'source', 'attribution'],
                    'answer' => 'Add UTM parameters to your campaign links (utm_source, utm_medium, utm_campaign, utm_term, utm_content). When a lead arrives through the website chatbot or a form, UTMs are captured automatically. You can see each lead\'s source in the detail panel and analyze performance by campaign in the reports.',
                ],
                [
                    'question' => 'How to view reports?',
                    'keywords' => ['reports', 'report', 'dashboard', 'metrics', 'statistics', 'charts', 'analytics', 'performance', 'results'],
                    'answer' => 'Go to Campaigns > Reports to see performance metrics by campaign, channel, and period. The main dashboard also shows charts for leads by stage, sales, and team activity. Use the date and campaign filters to refine your analysis.',
                ],
                [
                    'question' => 'How to export reports as PDF?',
                    'keywords' => ['export pdf', 'pdf', 'report pdf', 'download report', 'print report', 'generate pdf', 'pdf export'],
                    'answer' => 'On the Reports page, apply the desired filters and click the "Export PDF" button. The system will generate a PDF file with the charts and tables visible on screen. The file will be automatically downloaded in your browser.',
                ],
            ],
        ],

        // =====================================================================
        // 9. TASKS
        // =====================================================================
        'tasks' => [
            'title' => 'Tasks',
            'articles' => [
                [
                    'question' => 'How to create a task?',
                    'keywords' => ['create task', 'new task', 'add task', 'task', 'activity', 'to do', 'schedule task', 'todo'],
                    'answer' => 'Go to Tasks and click "New Task". Fill in the title, type, due date/time, and optionally associate it with a lead. You can assign the task to any team member. Overdue tasks appear highlighted in red for easy identification.',
                ],
                [
                    'question' => 'What task types are available?',
                    'keywords' => ['task types', 'task type', 'call', 'email', 'visit', 'meeting', 'whatsapp task', 'task categories', 'activity types'],
                    'answer' => 'The available task types are: Call, Email, Generic Task, Visit, WhatsApp, and Meeting. Each type has its own icon for quick identification. Choose the type that best describes the activity when creating a new task.',
                ],
                [
                    'question' => 'How to view tasks in the Kanban?',
                    'keywords' => ['kanban tasks', 'task board', 'view tasks', 'tasks kanban', 'organize tasks', 'task columns', 'task status'],
                    'answer' => 'On the Tasks page, switch between list and Kanban view using the buttons at the top. In Kanban mode, tasks are organized by status (To Do, In Progress, Done). Drag and drop to quickly update the status.',
                ],
            ],
        ],

        // =====================================================================
        // 10. CALENDAR
        // =====================================================================
        'calendar' => [
            'title' => 'Calendar',
            'articles' => [
                [
                    'question' => 'How to connect Google Calendar?',
                    'keywords' => ['google calendar', 'connect calendar', 'integrate calendar', 'link google', 'sync calendar', 'google agenda', 'calendar setup'],
                    'answer' => 'Go to Settings > Integrations and click "Google Calendar". Authorize access with your Google account. After connecting, your events will appear in the Syncro calendar and you can create new events that automatically sync with Google Calendar.',
                ],
                [
                    'question' => 'How to create or edit calendar events?',
                    'keywords' => ['create event', 'new event', 'edit event', 'schedule', 'appointment', 'book meeting', 'calendar event', 'meeting'],
                    'answer' => 'Go to Calendar and click on a time slot to create a new event, or click on an existing event to edit it. Fill in the title, date, time, description, and optionally associate it with a lead. Events created here automatically sync with Google Calendar if connected.',
                ],
                [
                    'question' => 'How does the AI agent use the calendar?',
                    'keywords' => ['ai calendar', 'agent calendar', 'ai schedule', 'ai appointment', 'check availability', 'ai booking', 'ai availability'],
                    'answer' => 'When the Calendar tool is enabled on the AI agent, it can check available time slots and create appointments automatically during customer conversations. The agent checks availability on Google Calendar and suggests times to the customer, all naturally within the conversation.',
                ],
            ],
        ],

        // =====================================================================
        // 11. SETTINGS
        // =====================================================================
        'settings' => [
            'title' => 'Settings',
            'articles' => [
                [
                    'question' => 'How to add users to the team?',
                    'keywords' => ['add user', 'new user', 'invite user', 'team', 'member', 'create user', 'manage users', 'team member', 'colleague'],
                    'answer' => 'Go to Settings > Users and click "New User". Fill in the name, email, and set the access level (Admin, Manager, or Viewer). The user will receive an email with login credentials. Admins have full access, Managers can manage leads and conversations, and Viewers can only browse.',
                ],
                [
                    'question' => 'How to create departments?',
                    'keywords' => ['department', 'create department', 'new department', 'team department', 'division', 'section', 'group'],
                    'answer' => 'Go to Settings > Departments and click "New Department". Define the name and conversation distribution strategy (Round Robin or Least Busy). Add department members. Conversations assigned to the department will be automatically distributed among members according to the chosen strategy.',
                ],
                [
                    'question' => 'How to manage integrations?',
                    'keywords' => ['integrations', 'connect', 'whatsapp', 'instagram', 'facebook', 'google', 'oauth', 'configure integration', 'link account'],
                    'answer' => 'In Settings > Integrations you\'ll find all available connections: WhatsApp (via QR Code), Instagram (via Facebook OAuth), Google Calendar, Facebook Ads, and Google Ads. Click on each integration to connect or check the connection status. Active integrations show a green indicator.',
                ],
                [
                    'question' => 'How to change the system language?',
                    'keywords' => ['language', 'portuguese', 'english', 'change language', 'switch language', 'translation', 'pt-br', 'locale', 'idioma'],
                    'answer' => 'Go to Settings > Profile and select the desired language (Portuguese or English). The change is applied immediately to the entire interface. Each user can choose their preferred language individually.',
                ],
                [
                    'question' => 'How to manage billing and subscription?',
                    'keywords' => ['subscription', 'plan', 'billing', 'payment', 'invoice', 'pix', 'credit card', 'cancel', 'upgrade', 'pricing', 'account'],
                    'answer' => 'Go to Settings > Billing to see your current plan, invoice history, and payment method. You can upgrade your plan, change the payment method (PIX or credit card), and view AI token consumption. Payments are processed securely via Asaas.',
                ],
            ],
        ],

        // =====================================================================
        // 12. AUTOMATIONS
        // =====================================================================
        'automations' => [
            'title' => 'Automations',
            'articles' => [
                [
                    'question' => 'How to create an automation?',
                    'keywords' => ['create automation', 'new automation', 'automation', 'automate', 'automatic rule', 'workflow', 'automatic flow'],
                    'answer' => 'Go to Settings > Automations and click "New Automation". Choose the trigger, define the conditions, and configure the actions. For example: "When a lead moves to the Proposal stage, send a WhatsApp message and add the VIP tag". Activate the automation and it will start working automatically.',
                ],
                [
                    'question' => 'What triggers are available?',
                    'keywords' => ['triggers', 'trigger', 'when', 'event', 'trigger condition', 'trigger types', 'automation trigger', 'fire condition'],
                    'answer' => 'Available triggers include: pipeline stage change, lead creation, lead update, tag added/removed, conversation opened/closed, specific date (e.g., birthday), message received, and deal closed. Each trigger can have additional conditions to refine when the automation should execute.',
                ],
                [
                    'question' => 'What actions can I configure in automations?',
                    'keywords' => ['automation actions', 'actions', 'action', 'send message', 'move lead', 'add tag', 'webhook', 'notify', 'available actions'],
                    'answer' => 'Available actions include: send WhatsApp message, move lead to another stage, add/remove tags, assign conversation to user/department, assign AI agent, send webhook, create lead note, send notification, and update lead fields. You can combine multiple actions in a single automation.',
                ],
            ],
        ],

        // =====================================================================
        // 13. LEAD LISTS
        // =====================================================================
        'lead_lists' => [
            'title' => 'Lead Lists',
            'articles' => [
                [
                    'question' => 'How to create a lead list?',
                    'keywords' => ['list', 'lists', 'create list', 'new list', 'segment', 'segmentation', 'group leads', 'lead list', 'lead segment'],
                    'answer' => 'Go to CRM > Lists and click "New List". Choose between static (you add leads manually) or dynamic (define filters and the list updates automatically). Lists are useful for segmenting leads for campaigns, bulk actions, or analysis.',
                ],
                [
                    'question' => 'What is the difference between static and dynamic lists?',
                    'keywords' => ['static', 'dynamic', 'list difference', 'list type', 'filter list', 'automatic list', 'smart list'],
                    'answer' => 'Static list: you manually add and remove leads — the list only changes when you edit it. Dynamic list: you define conditions (stage, tag, source, value, date, etc.) and the system automatically includes all matching leads. Dynamic lists update themselves as leads change.',
                ],
                [
                    'question' => 'What filters can I use in dynamic lists?',
                    'keywords' => ['list filters', 'list conditions', 'list rules', 'criteria', 'filter leads', 'dynamic conditions'],
                    'answer' => 'You can filter by: pipeline stage, pipeline, tag, source, assigned user, campaign, value, score, creation date, has email/phone, and has open conversation. Combine multiple filters to create precise segments. Use the "Preview" button to see how many leads match before saving.',
                ],
            ],
        ],

        // =====================================================================
        // 14. SALES GOALS
        // =====================================================================
        'sales_goals' => [
            'title' => 'Sales Goals',
            'articles' => [
                [
                    'question' => 'How to create sales goals?',
                    'keywords' => ['goal', 'goals', 'target', 'create goal', 'sales goal', 'sales target', 'quota', 'objective', 'team goals'],
                    'answer' => 'Go to CRM > Goals and click "New Goal". Define the period (monthly or weekly), the assigned salesperson, and the target (number of sales or revenue amount). The system shows progress bars to track each team member\'s performance in real time.',
                ],
                [
                    'question' => 'How to track goal progress?',
                    'keywords' => ['goal progress', 'track goal', 'performance', 'ranking', 'goal results', 'sales performance', 'goal status'],
                    'answer' => 'On the CRM > Goals page, you can see each salesperson\'s progress with visual bars showing the percentage achieved. Compare team performance side by side. Goals update automatically as sales are closed in the pipeline.',
                ],
            ],
        ],

        // =====================================================================
        // 15. NPS / SATISFACTION SURVEYS
        // =====================================================================
        'nps' => [
            'title' => 'NPS / Satisfaction Surveys',
            'articles' => [
                [
                    'question' => 'How to create an NPS survey?',
                    'keywords' => ['nps', 'survey', 'satisfaction', 'create survey', 'new survey', 'feedback', 'rating', 'customer satisfaction', 'net promoter'],
                    'answer' => 'Go to CRM > NPS and click "New Survey". Define the title and thank-you message. Each survey generates a unique link that can be shared with customers. Customers respond with a score from 0 to 10 and can leave an optional comment.',
                ],
                [
                    'question' => 'How to send NPS surveys to customers?',
                    'keywords' => ['send survey', 'send nps', 'share survey', 'survey link', 'whatsapp survey', 'distribute survey'],
                    'answer' => 'You have two options: 1) Copy the unique survey link and send it manually. 2) Use bulk send — select leads and send via WhatsApp automatically. You can also configure automatic sending when a sale is closed in the pipeline.',
                ],
                [
                    'question' => 'How to analyze NPS results?',
                    'keywords' => ['nps results', 'nps analysis', 'nps dashboard', 'nps metrics', 'promoters', 'detractors', 'nps score', 'nps report'],
                    'answer' => 'On the CRM > NPS page, the dashboard shows: overall NPS score (-100 to +100), percentage of promoters (9-10), passives (7-8), and detractors (0-6), monthly trend, per-salesperson analysis, and recent comments. Use this data to identify areas for improvement.',
                ],
                [
                    'question' => 'What does the NPS score mean?',
                    'keywords' => ['nps score', 'what is nps', 'nps meaning', 'how nps works', 'calculate nps', 'interpret nps', 'understand nps'],
                    'answer' => 'NPS (Net Promoter Score) ranges from -100 to +100. It is calculated by subtracting the percentage of detractors (0-6) from the percentage of promoters (9-10). Above 0 is positive, above 50 is excellent, above 75 is world-class. It is a global metric used to measure customer satisfaction and loyalty.',
                ],
            ],
        ],

        // =====================================================================
        // 16. PRODUCTS
        // =====================================================================
        'products' => [
            'title' => 'Products',
            'articles' => [
                [
                    'question' => 'How to add products?',
                    'keywords' => ['product', 'products', 'create product', 'add product', 'catalog', 'new product', 'product catalog'],
                    'answer' => 'Go to Settings > Products and click "New Product". Fill in the name, description, price, SKU, and select a category. You can also upload product images and documents. Products are available for associating with leads and for AI agents to reference during conversations.',
                ],
                [
                    'question' => 'How to associate products with a lead?',
                    'keywords' => ['associate product', 'product lead', 'link product', 'add product lead', 'sell product', 'lead product'],
                    'answer' => 'Open the lead detail panel and go to the "Products" tab. Click "Add Product", select from your catalog, set the quantity, and adjust the price if needed. This helps track which products each lead is interested in and calculate potential deal value.',
                ],
                [
                    'question' => 'How to organize products into categories?',
                    'keywords' => ['product category', 'categories', 'organize products', 'group products', 'product type', 'product organization'],
                    'answer' => 'On the Products page (Settings > Products), you can create categories to organize your catalog. Click "New Category", define the name, and then associate products with it. Categories make it easier to search and organize when you have many products.',
                ],
            ],
        ],

        // =====================================================================
        // 17. LEAD SCORING
        // =====================================================================
        'lead_scoring' => [
            'title' => 'Lead Scoring',
            'articles' => [
                [
                    'question' => 'What is lead scoring and how to configure it?',
                    'keywords' => ['scoring', 'score', 'lead scoring', 'lead points', 'classification', 'prioritize leads', 'lead rating', 'lead rank'],
                    'answer' => 'Lead scoring automatically assigns points to leads based on actions and characteristics, helping prioritize those most likely to close. Configure it in Settings > Scoring. Create rules like: "+10 points when VIP tag is added", "+5 when moved to Proposal stage". The score appears on the lead card in the Kanban and contacts list.',
                ],
                [
                    'question' => 'What criteria can I use for scoring?',
                    'keywords' => ['scoring criteria', 'scoring rules', 'scoring conditions', 'automatic scoring', 'point rules', 'score rules'],
                    'answer' => 'You can create rules based on: pipeline stage changes, tag additions, field completion (email, phone), deal value, lead source, and other interactions. Each rule defines how many points are added (or removed) when the condition is met.',
                ],
            ],
        ],

        // =====================================================================
        // 18. QUICK & SCHEDULED MESSAGES
        // =====================================================================
        'messages' => [
            'title' => 'Quick & Scheduled Messages',
            'articles' => [
                [
                    'question' => 'How to use quick messages in chat?',
                    'keywords' => ['quick message', 'shortcut', 'message template', 'quick reply', 'canned response', 'quick messages', 'saved reply'],
                    'answer' => 'Quick messages are reusable templates for common responses (greetings, pricing, FAQs). Access the Chats area and look for the Quick Messages option to create, edit, and organize your templates. During a conversation, use the shortcut to insert a message quickly without typing it all again.',
                ],
                [
                    'question' => 'How to schedule a WhatsApp message?',
                    'keywords' => ['schedule message', 'scheduled message', 'send later', 'schedule whatsapp', 'future message', 'timed message', 'delayed message'],
                    'answer' => 'Open a lead\'s detail panel and go to the scheduled messages section. Click "Schedule Message", write the text, and set the date and time for sending. The system will send it automatically at the scheduled time. Ideal for follow-ups, reminders, and planned outreach.',
                ],
            ],
        ],

        // =====================================================================
        // 19. PARTNER PROGRAM
        // =====================================================================
        'partners' => [
            'title' => 'Partner Program',
            'articles' => [
                [
                    'question' => 'How does the partner program work?',
                    'keywords' => ['partner', 'agency', 'partnership', 'partner program', 'reseller', 'referral', 'agency partner', 'affiliate'],
                    'answer' => 'The Partner Program allows agencies to register to manage client accounts. After registration and approval, the agency receives a unique referral code. Clients who sign up with this code are linked to the agency. The agency can view and access their clients\' accounts.',
                ],
                [
                    'question' => 'How to access a client\'s account as a partner?',
                    'keywords' => ['access client', 'impersonate', 'enter client account', 'view client', 'manage client', 'my clients', 'client access'],
                    'answer' => 'Go to My Clients (/agencia/meus-clientes) to see all clients referred by your agency. Click "Access" to enter the client\'s account. You\'ll have view access to monitor progress. To return to your own account, click "Return to my account" on the top banner.',
                ],
                [
                    'question' => 'How to link my account to a partner agency?',
                    'keywords' => ['link agency', 'agency code', 'partner code', 'associate agency', 'referral code', 'connect agency'],
                    'answer' => 'If you received a partner agency code, go to Settings and look for the option to link an agency code. Enter the code provided by the agency and confirm. Your account will be linked and the agency will be able to monitor your platform usage.',
                ],
            ],
        ],

        // =====================================================================
        // 20. ONBOARDING
        // =====================================================================
        'onboarding' => [
            'title' => 'Onboarding',
            'articles' => [
                [
                    'question' => 'How does onboarding work?',
                    'keywords' => ['onboarding', 'initial setup', 'wizard', 'setup', 'first time', 'setup assistant', 'getting started wizard'],
                    'answer' => 'When you create a new account, you go through a guided onboarding wizard that sets up your CRM automatically. The system uses AI to understand your business type and generates customized pipelines, stages, tags, chatbot flows, and AI agent. You can skip the onboarding if you prefer manual setup.',
                ],
            ],
        ],
    ],
];
