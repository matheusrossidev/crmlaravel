<?php

declare(strict_types=1);

return [

    // ── Page ────────────────────────────────────────────────────────
    'page_title'    => 'Instagram Automations',
    'page_subtitle' => 'Automatically reply to comments and send DMs based on keywords.',

    // ── Banner ──────────────────────────────────────────────────────
    'banner_not_connected'      => 'Instagram is not connected. To use Automations,',
    'banner_go_to_integrations' => 'go to Integrations',
    'banner_connect_account'    => 'and connect your account.',

    // ── Card header ─────────────────────────────────────────────────
    'card_title'      => 'Comment Automations',
    'btn_new'         => 'New Automation',

    // ── Item labels ─────────────────────────────────────────────────
    'specific_post'   => 'Specific post',
    'all_posts'       => 'All posts',
    'match_all'       => 'All keywords',
    'match_any'       => 'Any keyword',
    'keywords_count'  => ':count keyword(s)',
    'comments_replied' => ':count comment(s) replied',
    'dms_sent'        => ':count DM(s) sent',

    // ── Toggle / actions ────────────────────────────────────────────
    'toggle_active'   => 'Active',
    'toggle_inactive' => 'Inactive',
    'btn_edit'        => 'Edit',
    'btn_delete'      => 'Delete',

    // ── Empty state ─────────────────────────────────────────────────
    'empty_title'     => 'No automations created yet.',
    'empty_hint'      => 'Click <strong>New Automation</strong> to get started.',

    // ── Drawer ──────────────────────────────────────────────────────
    'drawer_title_new'  => 'New Automation',
    'drawer_title_edit' => 'Edit Automation',

    // Name
    'label_name'             => 'Name',
    'label_name_optional'    => '(optional)',
    'placeholder_name'       => 'E.g.: Reply about pricing',

    // Post scope
    'label_target_post'      => 'Target post',
    'scope_all_posts'        => 'All posts',
    'scope_specific_post'    => 'Specific post',

    // Post picker
    'btn_load_more'          => 'Load more',
    'error_load_posts'       => 'Error loading posts.',

    // Keywords
    'label_keywords'         => 'Keywords',
    'keywords_hint'          => '(Press Enter or comma to add)',
    'placeholder_keyword'    => 'Type a keyword...',

    // Match type
    'label_match_type'       => 'Match type',
    'match_any_or'           => 'Any keyword (OR)',
    'match_all_and'          => 'All keywords (AND)',

    // Reply comment
    'label_reply_comment'          => 'Reply to comment',
    'label_reply_comment_optional' => '(optional)',
    'placeholder_reply_comment'    => 'Public reply posted on the comment...',

    // DM builder
    'label_send_dm'          => 'Send DM',
    'label_send_dm_optional' => '(optional — message sequence)',
    'dm_block_text'          => 'Text',
    'dm_block_image'         => 'Image',
    'dm_placeholder_url'     => 'https://public.com/image.jpg',
    'dm_links_hint'          => 'Links become clickable automatically',
    'dm_placeholder_message' => 'Write your message...',
    'dm_preview_label'       => 'Instagram DM Preview',
    'dm_preview_placeholder' => 'Preview will appear here...',

    // Button Template Buttons
    'dm_buttons_label'       => 'Buttons',
    'dm_buttons_optional'    => '(optional)',
    'dm_btn_max_3'           => 'max. 3 buttons',
    'dm_btn_placeholder'     => 'Button text (max. 20 chars)',
    'dm_btn_title_ph'        => 'Button title',
    'dm_btn_remove'          => 'Remove',
    'dm_btn_add'             => '+ Button',

    // Drawer footer
    'btn_cancel'             => 'Cancel',
    'btn_save'               => 'Save',
    'btn_saving'             => 'Saving…',

    // ── Confirm delete dialog ───────────────────────────────────────
    'confirm_delete_title'   => 'Delete Automation',
    'confirm_delete_message' => 'Are you sure you want to delete this automation? This action cannot be undone.',
    'btn_confirm_delete'     => 'Delete',
    'btn_deleting'           => 'Deleting…',

    // ── Alerts / Toastr ─────────────────────────────────────────────
    'alert_error_delete'         => 'Error deleting.',
    'alert_network_error'        => 'Network error. Please try again.',
    'alert_keyword_required'     => 'Add at least one keyword.',
    'alert_action_required'      => 'Define at least one action: comment reply or DM.',
    'alert_error_save'           => 'Error saving.',
    'alert_error_save_detail'    => 'Error saving: :message',
    'toastr_max_buttons'         => 'Maximum of 3 buttons per message (Button Template).',

    // Simplified DM
    'dm_single_placeholder'      => 'E.g.: Hi! I noticed you were interested in our content 😊 How can I help?',
    'dm_chatbot_hint'            => 'Need a flow with questions, conditions and actions? Use the',
    'dm_chatbot_link'            => 'Chatbot Builder',
    'dm_chatbot_suffix'          => 'with the "Commented on post" trigger.',
];
