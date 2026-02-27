@php
$classes = [
    'message_received'     => 'msg',
    'conversation_created' => 'conv',
    'lead_created'         => 'lead',
    'lead_stage_changed'   => 'stage',
    'lead_won'             => 'won',
    'lead_lost'            => 'lost',
];
$icons = [
    'message_received'     => 'bi-chat-dots',
    'conversation_created' => 'bi-plus-circle',
    'lead_created'         => 'bi-person-plus',
    'lead_stage_changed'   => 'bi-arrow-right-circle',
    'lead_won'             => 'bi-trophy',
    'lead_lost'            => 'bi-x-circle',
];
$labels = [
    'message_received'     => 'Mensagem recebida',
    'conversation_created' => 'Nova conversa',
    'lead_created'         => 'Lead criado',
    'lead_stage_changed'   => 'Lead movido de etapa',
    'lead_won'             => 'Lead ganho',
    'lead_lost'            => 'Lead perdido',
];
$cls   = $classes[$auto->trigger_type] ?? 'lead';
$icon  = $icons[$auto->trigger_type]   ?? 'bi-lightning-charge';
$label = $labels[$auto->trigger_type]  ?? $auto->trigger_type;
@endphp
<span class="trigger-badge {{ $cls }}">
    <i class="bi {{ $icon }}"></i> {{ $label }}
</span>
