@php
    $tourUser = auth()->user();
    $toursCompleted = $tourUser->dashboard_config['tours_completed'] ?? [];
    $onboardingDone = $tourUser->tenant?->onboarding_completed_at !== null;

    $tourPage = null;
    if (request()->routeIs('dashboard', 'inicio') && !($toursCompleted['global'] ?? false) && $onboardingDone) {
        $tourPage = 'global';
    } elseif (request()->routeIs('crm.kanban') && !($toursCompleted['kanban'] ?? false)) {
        $tourPage = 'kanban';
    } elseif (request()->routeIs('leads.index') && !($toursCompleted['contacts'] ?? false)) {
        $tourPage = 'contacts';
    } elseif (request()->routeIs('leads.profile') && !($toursCompleted['lead_profile'] ?? false)) {
        $tourPage = 'lead_profile';
    } elseif (request()->routeIs('tasks.index') && !($toursCompleted['tasks'] ?? false)) {
        $tourPage = 'tasks';
    } elseif (request()->routeIs('calendar.*') && !($toursCompleted['calendar'] ?? false)) {
        $tourPage = 'calendar';
    } elseif (request()->routeIs('lists.*') && !($toursCompleted['lists'] ?? false)) {
        $tourPage = 'lists';
    } elseif (request()->routeIs('goals.*') && !($toursCompleted['goals'] ?? false)) {
        $tourPage = 'goals';
    } elseif (request()->routeIs('chats.*') && !($toursCompleted['chats'] ?? false)) {
        $tourPage = 'chats';
    } elseif (request()->routeIs('chatbot.flows.index') && !($toursCompleted['chatbot'] ?? false)) {
        $tourPage = 'chatbot';
    } elseif (request()->routeIs('ai.agents.index') && !($toursCompleted['ai_agents'] ?? false)) {
        $tourPage = 'ai_agents';
    } elseif (request()->routeIs('settings.automations') && !($toursCompleted['automations'] ?? false)) {
        $tourPage = 'automations';
    } elseif (request()->routeIs('reports.index') && !($toursCompleted['reports'] ?? false)) {
        $tourPage = 'reports';
    } elseif (request()->routeIs('campaigns.*') && !($toursCompleted['campaigns'] ?? false)) {
        $tourPage = 'campaigns';
    } elseif (request()->routeIs('nps.*') && !($toursCompleted['nps'] ?? false)) {
        $tourPage = 'nps';
    } elseif (request()->routeIs('settings.profile') && !($toursCompleted['profile'] ?? false)) {
        $tourPage = 'profile';
    } elseif (request()->routeIs('settings.integrations*') && !($toursCompleted['integrations'] ?? false)) {
        $tourPage = 'integrations';
    } elseif (request()->routeIs('settings.pipelines') && !($toursCompleted['pipelines'] ?? false)) {
        $tourPage = 'pipelines';
    } elseif (request()->routeIs('settings.scoring') && !($toursCompleted['scoring'] ?? false)) {
        $tourPage = 'scoring';
    } elseif (request()->routeIs('settings.billing') && !($toursCompleted['billing'] ?? false)) {
        $tourPage = 'billing';
    } elseif (request()->routeIs('settings.products') && !($toursCompleted['products'] ?? false)) {
        $tourPage = 'products';
    } elseif (request()->routeIs('settings.custom-fields') && !($toursCompleted['custom_fields'] ?? false)) {
        $tourPage = 'custom_fields';
    } elseif (request()->routeIs('settings.lost-reasons') && !($toursCompleted['lost_reasons'] ?? false)) {
        $tourPage = 'lost_reasons';
    } elseif (request()->routeIs('settings.tags') && !($toursCompleted['tags'] ?? false)) {
        $tourPage = 'tags';
    } elseif (request()->routeIs('settings.sequences*') && !($toursCompleted['sequences'] ?? false)) {
        $tourPage = 'sequences';
    } elseif (request()->routeIs('settings.notifications') && !($toursCompleted['notifications'] ?? false)) {
        $tourPage = 'notifications';
    } elseif (request()->routeIs('settings.users') && !($toursCompleted['users'] ?? false)) {
        $tourPage = 'users';
    } elseif (request()->routeIs('settings.departments') && !($toursCompleted['departments'] ?? false)) {
        $tourPage = 'departments';
    } elseif (request()->routeIs('settings.api-keys') && !($toursCompleted['api_keys'] ?? false)) {
        $tourPage = 'api_keys';
    } elseif (request()->routeIs('settings.pwa') && !($toursCompleted['pwa'] ?? false)) {
        $tourPage = 'pwa';
    } elseif (request()->routeIs('settings.audit-log') && !($toursCompleted['audit_log'] ?? false)) {
        $tourPage = 'audit_log';
    } elseif (request()->routeIs('settings.ig-automations*') && !($toursCompleted['ig_automations'] ?? false)) {
        $tourPage = 'ig_automations';
    } elseif (request()->routeIs('partner.dashboard') && !($toursCompleted['partner_dashboard'] ?? false)) {
        $tourPage = 'partner_dashboard';
    } elseif (request()->routeIs('partner.resources.*') && !($toursCompleted['partner_resources'] ?? false)) {
        $tourPage = 'partner_resources';
    } elseif (request()->routeIs('partner.courses.*') && !($toursCompleted['partner_courses'] ?? false)) {
        $tourPage = 'partner_courses';
    }
@endphp

@if($tourPage)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<style>
.driver-popover{font-family:'DM Sans',sans-serif!important;border-radius:14px!important;box-shadow:0 8px 32px rgba(0,0,0,.15)!important;}
.driver-popover-title{font-size:15px!important;font-weight:700!important;color:#1a1d23!important;}
.driver-popover-description{font-size:13px!important;color:#374151!important;line-height:1.6!important;}
.driver-popover-next-btn,.driver-popover-close-btn-text{background:#0085f3!important;color:#fff!important;border-radius:8px!important;font-weight:600!important;font-size:12px!important;padding:6px 16px!important;border:none!important;text-shadow:none!important;}
.driver-popover-prev-btn{border-radius:8px!important;font-size:12px!important;padding:6px 16px!important;font-weight:600!important;}
.driver-popover-footer{gap:8px!important;}
.driver-popover-progress-text{font-size:11px!important;color:#9ca3af!important;}
</style>
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var TOUR = @json($tourPage);
    var T = {!! json_encode(__('tour')) !!};
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    var URL = @json(route('tour.complete'));

    function done(name) {
        fetch(URL, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({tour:name}) }).catch(function(){});
    }

    function t(k) { return T[k] || k; }

    function s(el, title, desc) {
        var o = { popover: { title: t(title), description: t(desc) } };
        if (el) o.element = el;
        return o;
    }

    function pick(sel) {
        var parts = sel.split(',');
        for (var i = 0; i < parts.length; i++) {
            if (document.querySelector(parts[i].trim())) return parts[i].trim();
        }
        return null;
    }

    function build(defs) {
        return defs.map(function(d) {
            if (!d[0]) return s(null, d[1], d[2]);
            var el = pick(d[0]);
            return el ? s(el, d[1], d[2]) : null;
        }).filter(Boolean);
    }

    var defs = {
        global: [
            ['.welcome-banner,.stats-grid', 'global_welcome_title', 'global_welcome_desc'],
            ['#navbarMenu > a.nm-item:first-of-type', 'global_home_title', 'global_home_desc'],
            ['#navbarMenu > a.nm-item:nth-of-type(2)', 'global_chats_title', 'global_chats_desc'],
            ['#navbarMenu > .nm-dropdown:nth-of-type(1)', 'global_crm_title', 'global_crm_desc'],
            ['#navbarMenu > .nm-dropdown:nth-of-type(2)', 'global_automation_title', 'global_automation_desc'],
            ['#navbarMenu > .nm-dropdown:nth-of-type(3)', 'global_reports_title', 'global_reports_desc'],
            ['#navbarMenu > .nm-dropdown:nth-of-type(4)', 'global_settings_title', 'global_settings_desc'],
            ['[onclick="openGlobalSearch()"]', 'global_search_title', 'global_search_desc'],
            ['#notif-bell-btn', 'global_notifications_title', 'global_notifications_desc'],
            ['.shw-bubble', 'global_sophia_title', 'global_sophia_desc'],
            ['.stat-card:first-child,.stats-grid > div:first-child', 'global_metrics_title', 'global_metrics_desc'],
            ['.trial-widget', 'global_plan_title', 'global_plan_desc'],
            [null, 'global_done_title', 'global_done_desc'],
        ],
        kanban: [
            ['.kanban-board', 'kanban_board_title', 'kanban_board_desc'],
            ['.kanban-col:first-child', 'kanban_stages_title', 'kanban_stages_desc'],
            ['.kanban-card:first-child,.kanban-list', 'kanban_cards_title', 'kanban_cards_desc'],
            ['#filterForm,.kanban-filters', 'kanban_filters_title', 'kanban_filters_desc'],
            ['#btnImportLead,[id*="import"]', 'kanban_import_title', 'kanban_import_desc'],
        ],
        contacts: [
            ['.leads-table-wrap,.leads-table', 'contacts_table_title', 'contacts_table_desc'],
            ['#searchInput,[name="search"]', 'contacts_search_title', 'contacts_search_desc'],
            ['#btnNovoLead,.btn-primary-sm:last-child', 'contacts_new_lead_title', 'contacts_new_lead_desc'],
            ['[href*="duplicatas"]', 'contacts_duplicates_title', 'contacts_duplicates_desc'],
        ],
        lead_profile: [
            ['.lp-hero', 'lead_profile_hero_title', 'lead_profile_hero_desc'],
            ['.lp-tabs-nav', 'lead_profile_tabs_title', 'lead_profile_tabs_desc'],
            ['.lp-hero-name', 'lead_profile_actions_title', 'lead_profile_actions_desc'],
            ['.lp-pipeline', 'lead_profile_pipeline_title', 'lead_profile_pipeline_desc'],
            ['.lp-grid > div:last-child', 'lead_profile_sidebar_title', 'lead_profile_sidebar_desc'],
        ],
        tasks: [
            ['.task-filters,[name="status"]', 'tasks_filters_title', 'tasks_filters_desc'],
            ['table,.task-table-wrap', 'tasks_table_title', 'tasks_table_desc'],
            ['.btn-primary-sm,[onclick*="openTaskDrawer"]', 'tasks_new_task_title', 'tasks_new_task_desc'],
        ],
        calendar: [
            ['.cal-layout,.fc', 'calendar_layout_title', 'calendar_layout_desc'],
            ['.cal-sidebar', 'calendar_sidebar_title', 'calendar_sidebar_desc'],
            ['.btn-primary-sm', 'calendar_new_event_title', 'calendar_new_event_desc'],
        ],
        lists: [
            ['.content-card,table', 'lists_overview_title', 'lists_overview_desc'],
            ['.list-type-badge,.list-type-static', 'lists_types_title', 'lists_types_desc'],
            ['.btn-primary-sm', 'lists_new_list_title', 'lists_new_list_desc'],
        ],
        goals: [
            ['.g-summary', 'goals_summary_title', 'goals_summary_desc'],
            ['.g-tabs', 'goals_tabs_title', 'goals_tabs_desc'],
            ['.gc,.team-card', 'goals_card_title', 'goals_card_desc'],
            ['.btn-primary-sm', 'goals_new_goal_title', 'goals_new_goal_desc'],
        ],
        chats: [
            ['.wa-sidebar,.conv-list', 'chats_sidebar_title', 'chats_sidebar_desc'],
            ['.wa-chat-area,#chatArea', 'chats_messages_title', 'chats_messages_desc'],
            ['.wa-details,#detailsPanel', 'chats_details_title', 'chats_details_desc'],
            ['#assignSelect,.assign-section', 'chats_assign_title', 'chats_assign_desc'],
        ],
        chatbot: [
            ['.flows-grid,.content-card', 'chatbot_grid_title', 'chatbot_grid_desc'],
            ['.btn-primary-sm,a[href*="onboarding"]', 'chatbot_create_title', 'chatbot_create_desc'],
            ['.badge-active,.flow-badges', 'chatbot_badges_title', 'chatbot_badges_desc'],
        ],
        ai_agents: [
            ['.agents-grid,.content-card', 'ai_agents_grid_title', 'ai_agents_grid_desc'],
            ['.agent-card:first-child', 'ai_agents_card_title', 'ai_agents_card_desc'],
            ['[href*="media"],.agent-media', 'ai_agents_media_title', 'ai_agents_media_desc'],
            ['[onclick*="testChat"],.btn-test', 'ai_agents_test_title', 'ai_agents_test_desc'],
        ],
        automations: [
            ['.at-wrap,table', 'automations_table_title', 'automations_table_desc'],
            ['.trigger-badge', 'automations_triggers_title', 'automations_triggers_desc'],
            ['.btn-primary-sm', 'automations_new_title', 'automations_new_desc'],
        ],
        reports: [
            ['.report-filter-wrap,form', 'reports_filters_title', 'reports_filters_desc'],
            ['.stat-card:first-child,.kpi-card:first-child', 'reports_kpis_title', 'reports_kpis_desc'],
            ['canvas,.chart-card', 'reports_charts_title', 'reports_charts_desc'],
        ],
        campaigns: [
            ['.kpi-grid', 'campaigns_kpis_title', 'campaigns_kpis_desc'],
            ['.top-row,.top-card', 'campaigns_top_title', 'campaigns_top_desc'],
            ['.ranking-table,table', 'campaigns_ranking_title', 'campaigns_ranking_desc'],
        ],
        nps: [
            ['.nps-kpi-grid', 'nps_kpis_title', 'nps_kpis_desc'],
            ['.nps-grid-2,canvas', 'nps_charts_title', 'nps_charts_desc'],
            ['.btn-primary-sm', 'nps_new_survey_title', 'nps_new_survey_desc'],
        ],
        profile: [
            ['.profile-card:first-child,form', 'profile_form_title', 'profile_form_desc'],
            ['.avatar-wrap,.profile-avatar', 'profile_avatar_title', 'profile_avatar_desc'],
        ],
        integrations: [
            ['.integration-card:first-child,.content-card:first-child', 'integrations_cards_title', 'integrations_cards_desc'],
            ['.btn-connect,.btn-primary-sm', 'integrations_connect_title', 'integrations_connect_desc'],
            ['.status-badge,.badge', 'integrations_status_title', 'integrations_status_desc'],
        ],
        pipelines: [
            ['.pipeline-list,.content-card', 'pipelines_list_title', 'pipelines_list_desc'],
            ['.stage-row,.sortable', 'pipelines_stages_title', 'pipelines_stages_desc'],
            ['.is-won,.is-lost', 'pipelines_outcome_title', 'pipelines_outcome_desc'],
        ],
        scoring: [
            ['table,.content-card', 'scoring_table_title', 'scoring_table_desc'],
            ['.btn-primary-sm', 'scoring_new_rule_title', 'scoring_new_rule_desc'],
        ],
        billing: [
            ['.current-plan-card,.billing-sidebar', 'billing_plan_title', 'billing_plan_desc'],
            ['.plan-card,.billing-main', 'billing_upgrade_title', 'billing_upgrade_desc'],
        ],
        partner_dashboard: [
            ['.ph-dark,.ph-rank-section', 'partner_dashboard_rank_title', 'partner_dashboard_rank_desc'],
            ['.ph-kpi-row', 'partner_dashboard_kpis_title', 'partner_dashboard_kpis_desc'],
            ['.ph-link-box', 'partner_dashboard_code_title', 'partner_dashboard_code_desc'],
            ['.ph-card table,.client-table', 'partner_dashboard_clients_title', 'partner_dashboard_clients_desc'],
            ['[onclick*="saque"],.btn-withdraw', 'partner_dashboard_withdraw_title', 'partner_dashboard_withdraw_desc'],
        ],
        partner_resources: [
            ['.res-grid', 'partner_resources_grid_title', 'partner_resources_grid_desc'],
            ['.res-filter', 'partner_resources_filters_title', 'partner_resources_filters_desc'],
        ],
        partner_courses: [
            ['.course-grid', 'partner_courses_grid_title', 'partner_courses_grid_desc'],
            ['.course-progress,.course-bar', 'partner_courses_progress_title', 'partner_courses_progress_desc'],
            ['.course-cert-badge', 'partner_courses_certificate_title', 'partner_courses_certificate_desc'],
        ],
        products: [
            ['table,.content-card', 'products_table_title', 'products_table_desc'],
            ['.btn-primary-sm', 'products_new_title', 'products_new_desc'],
        ],
        custom_fields: [
            ['table,.content-card', 'custom_fields_table_title', 'custom_fields_table_desc'],
            ['.btn-primary-sm', 'custom_fields_new_title', 'custom_fields_new_desc'],
        ],
        lost_reasons: [
            ['table,.content-card', 'lost_reasons_table_title', 'lost_reasons_table_desc'],
            ['.btn-primary-sm', 'lost_reasons_new_title', 'lost_reasons_new_desc'],
        ],
        tags: [
            ['table,.content-card', 'tags_table_title', 'tags_table_desc'],
            ['.btn-primary-sm', 'tags_new_title', 'tags_new_desc'],
        ],
        sequences: [
            ['table,.content-card', 'sequences_table_title', 'sequences_table_desc'],
            ['.btn-primary-sm', 'sequences_new_title', 'sequences_new_desc'],
        ],
        notifications: [
            ['.content-card:first-child,form', 'notifications_prefs_title', 'notifications_prefs_desc'],
            ['[type="checkbox"],.form-check', 'notifications_toggles_title', 'notifications_toggles_desc'],
        ],
        users: [
            ['table,.content-card', 'users_table_title', 'users_table_desc'],
            ['.btn-primary-sm', 'users_new_title', 'users_new_desc'],
        ],
        departments: [
            ['table,.content-card', 'departments_table_title', 'departments_table_desc'],
            ['.btn-primary-sm', 'departments_new_title', 'departments_new_desc'],
        ],
        api_keys: [
            ['table,.content-card', 'api_keys_table_title', 'api_keys_table_desc'],
            ['.btn-primary-sm', 'api_keys_new_title', 'api_keys_new_desc'],
        ],
        pwa: [
            ['.content-card,.pwa-card', 'pwa_install_title', 'pwa_install_desc'],
        ],
        audit_log: [
            ['table,.content-card', 'audit_table_title', 'audit_table_desc'],
            ['form,[name="action"]', 'audit_filters_title', 'audit_filters_desc'],
        ],
        ig_automations: [
            ['table,.content-card', 'ig_automations_table_title', 'ig_automations_table_desc'],
            ['.btn-primary-sm', 'ig_automations_new_title', 'ig_automations_new_desc'],
        ],
    };

    var steps = build(defs[TOUR] || []);
    if (!steps.length) return;

    setTimeout(function() {
        var d = window.driver.js.driver({
            showProgress: true,
            animate: true,
            smoothScroll: true,
            allowClose: true,
            overlayColor: 'rgba(0,0,0,0.55)',
            stagePadding: 10,
            stageRadius: 12,
            nextBtnText: t('btn_next'),
            prevBtnText: t('btn_prev'),
            doneBtnText: t('btn_done'),
            onDestroyStarted: function() { done(TOUR); d.destroy(); },
            steps: steps,
        });
        d.drive();
    }, 1000);
});
</script>
@endif
