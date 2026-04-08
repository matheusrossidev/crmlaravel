<?php

return [
    'title'       => 'Nurture Sequences',
    'subtitle'    => 'Create automated message cadences to nurture leads over time',
    'new'         => 'New Sequence',
    'edit'        => 'Edit Sequence',
    'no_sequences' => 'No sequences created.',
    'no_sequences_sub' => 'Create sequences to send automated messages over time.',

    // Table
    'col_name'      => 'Name',
    'col_steps'     => 'Steps',
    'col_enrolled'  => 'Enrolled',
    'col_completed' => 'Completed',
    'col_active'    => 'Active',
    'col_status'    => 'Status',

    // Form
    'field_name'        => 'Sequence name',
    'field_name_ph'     => 'E.g.: Post-Contact Nurture',
    'field_desc'        => 'Description',
    'field_desc_ph'     => 'Brief description of the objective',
    'field_exit_reply'  => 'Stop if lead replies',
    'field_exit_stage'  => 'Stop if lead changes stage',
    'section_settings'  => 'Settings',
    'section_steps'     => 'Sequence Steps',

    // Steps
    'step_type'          => 'Type',
    'step_delay'         => 'Wait',
    'step_delay_help'    => 'Wait time after previous step',
    'step_message'       => 'Message',
    'step_wait_reply'    => 'Wait for Reply',
    'step_condition'     => 'Condition',
    'step_action'        => 'Action',
    'step_body'          => 'Message text',
    'step_body_ph'       => 'Hi {{name}}, how are you? ...',
    'step_send_via'      => 'Send via',
    'step_send_via_auto' => 'Automatic (existing conversation or tenant default)',
    'step_timeout'       => 'Timeout (minutes)',
    'step_add'           => 'Add Step',
    'step_remove'        => 'Remove',

    // Delay units
    'minutes' => 'minutes',
    'hours'   => 'hours',
    'days'    => 'days',

    // Actions
    'btn_save'   => 'Save',
    'btn_cancel' => 'Cancel',

    // Toasts
    'toast_created' => 'Sequence created!',
    'toast_updated' => 'Sequence updated!',
    'toast_deleted' => 'Sequence deleted.',
    'toast_error'   => 'Error saving.',
    'toast_toggled_on'  => 'Sequence activated!',
    'toast_toggled_off' => 'Sequence deactivated.',

    // Confirm
    'confirm_delete_title' => 'Delete sequence?',
    'confirm_delete_msg'   => 'All enrolled leads will be removed. This action cannot be undone.',
    'confirm_delete_btn'   => 'Yes, delete',

    // Variables
    'variables_help' => 'Variables: {{name}}, {{company}}, {{email}}, {{phone}}, {{stage}}, {{score}}',

    // Lead badge
    'badge_active'    => 'In sequence',
    'badge_step'      => 'Step :current/:total',

    // Nav
    'nav_title' => 'Sequences',
];
