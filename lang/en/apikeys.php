<?php

declare(strict_types=1);

return [

    // ── Page ──
    'page_title' => 'API / Webhooks',

    // ── API Keys card ──
    'your_api_keys'   => 'Your API Keys',
    'new_api_key'     => 'New API Key',

    // ── Key item ──
    'created_at'      => 'Created on :date',
    'last_used'       => 'Last used: :time',
    'never_used'      => 'Never used',
    'badge_active'    => 'Active',
    'badge_revoked'   => 'Revoked',
    'btn_revoke'      => 'Revoke',

    // ── Empty state ──
    'empty_title'     => 'No API Keys created yet.',
    'empty_cta'       => 'Click <strong>New API Key</strong> to create one.',

    // ── Endpoints card ──
    'endpoints_title' => 'Endpoint Documentation',
    'endpoints_intro' => 'Include the header <code style="background:#f0f4ff;color:#6366f1;padding:2px 6px;border-radius:4px;font-size:12px;">X-API-Key: your_key</code> in all requests. Base URL:',

    // ── Endpoint descriptions ──
    'ep_post_leads'       => 'Create new lead — use the builder to assemble the payload',
    'ep_get_lead'         => 'Get lead by ID',
    'ep_put_stage'        => 'Move lead to another stage',
    'ep_put_won'          => 'Mark lead as won',
    'ep_put_lost'         => 'Mark lead as lost',
    'ep_delete_lead'      => 'Delete lead',
    'ep_get_pipelines'    => 'List available pipelines and stages',

    // ── Builder section titles ──
    'builder_main_fields'    => 'Main Fields',
    'builder_pipeline_stage' => 'Pipeline & Stage',
    'builder_required'       => 'required',
    'builder_custom_fields'  => 'Custom Fields',
    'builder_campaign_utm'   => 'Campaign & UTM',
    'builder_utm_hint'       => '(automatic attribution)',
    'builder_no_pipeline'    => 'No pipeline configured. Create one at',
    'builder_settings_funnels' => 'Settings → Funnels',
    'builder_no_tags'        => 'No tags configured',
    'builder_campaign_none'  => '— None —',
    'builder_req'            => 'req',

    // ── Builder curl preview ──
    'curl_generated' => 'Generated cURL',
    'btn_copy'       => 'Copy',
    'btn_copied'     => 'Copied!',

    // ── Mini builder labels ──
    'label_lead_id'       => 'Lead ID',
    'label_pipeline'      => 'Pipeline',
    'label_stage'         => 'Stage',
    'label_pipeline_filter' => 'Pipeline',
    'label_filter_hint'   => '(filter)',
    'label_won_stage'     => 'Won Stage',
    'label_lost_stage'    => 'Lost Stage',
    'label_value_optional' => 'Value (optional)',
    'label_reason_optional' => 'Reason ID (optional)',
    'label_reason_placeholder' => 'Reason ID',

    // ── Stage dropdowns (JS) ──
    'no_won_stages'  => '— No won stages in this pipeline —',
    'no_lost_stages' => '— No lost stages in this pipeline —',
    'no_stages'      => '— No stages available —',

    // ── How-to sidebar ──
    'how_to_use'     => 'How to use',
    'step1_title'    => '1. Generate an API Key',
    'step1_text'     => 'Click <em>New API Key</em>, give it a name to identify where it will be used (e.g. "Website", "Automation") and copy the key.',
    'step2_title'    => '2. Store it securely',
    'step2_text'     => 'The full key is displayed <strong>only once</strong>. Keep it in a safe place.',
    'step3_title'    => '3. Include in the header',
    'step3_example'  => 'X-API-Key: crm_your_key_here',
    'step4_title'    => '4. Base URL',

    'builder_tip_title' => 'Interactive builder',
    'builder_tip_text'  => 'Expand <span class="endpoint-method method-post" style="font-size:10px;">POST</span> <code style="font-size:11px;">/leads</code> to use the builder — select fields, pipeline and stage and see the generated cURL in real time.',

    'isolation_notice'  => 'All requests are isolated per account.',

    'custom_fields_title' => 'Custom fields',
    'custom_fields_hint'  => 'Pass in <code style="font-size:11px;">custom_fields</code>:',

    // ── Modal: New API Key ──
    'modal_new_key_title' => 'New API Key',
    'modal_key_name_label' => 'Key name',
    'modal_key_name_placeholder' => 'E.g.: Website, Landing Page, Automation...',
    'modal_cancel'        => 'Cancel',
    'modal_create'        => 'Create',
    'modal_creating'      => 'Creating...',

    // ── Modal: Key reveal ──
    'modal_copy_warning'  => 'Copy now! This key <strong>will not be shown again</strong>.',
    'modal_done_copied'   => 'Done, I\'ve copied it',

    // ── Toastr messages ──
    'toast_name_required'     => 'Please enter a name for the API Key.',
    'toast_create_error'      => 'Error creating API Key.',
    'toast_connection_error'  => 'Connection error.',
    'toast_revoked_success'   => 'API Key revoked.',
    'toast_revoke_error'      => 'Error revoking key.',

    // ── Confirm dialog ──
    'confirm_revoke_title'   => 'Revoke API Key',
    'confirm_revoke_message' => 'Systems using this key will lose access immediately.',
    'confirm_revoke_btn'     => 'Revoke',

];
