<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventReminder;
use App\Models\Lead;
use Carbon\Carbon;

class EventReminderService
{
    private const DEFAULT_TEMPLATE = 'Olá {{lead_name}}! Lembrete: você tem {{event_title}} agendado(a) para {{event_date}} às {{event_time}}. Nos vemos em breve!';

    /**
     * Create WhatsApp reminders for a calendar event.
     */
    public function createRemindersForEvent(array $params): void
    {
        $tenantId       = $params['tenant_id'];
        $leadId         = $params['lead_id'] ?? null;
        $conversationId = $params['conversation_id'] ?? null;
        $aiAgentId      = $params['ai_agent_id'] ?? null;
        $googleEventId  = $params['google_event_id'] ?? null;
        $eventTitle     = $params['event_title'] ?? 'Evento';
        $eventStartsAt  = $params['event_starts_at']; // Carbon instance
        $eventLocation  = $params['event_location'] ?? '';
        $offsets        = $params['offsets'] ?? [1440, 60];
        $template       = $params['template'] ?? null;

        if (!$leadId) {
            return;
        }

        $lead = Lead::withoutGlobalScope('tenant')->find($leadId);
        $leadName = $lead?->name ?? 'Cliente';
        $resolvedTemplate = $template ?: self::DEFAULT_TEMPLATE;

        $tz = config('app.timezone', 'America/Sao_Paulo');

        foreach ($offsets as $offset) {
            $sendAt = $eventStartsAt->copy()->subMinutes((int) $offset);

            // Skip if send_at is in the past
            if ($sendAt->isPast()) {
                continue;
            }

            $body = $this->renderTemplate($resolvedTemplate, [
                'lead_name'      => $leadName,
                'event_title'    => $eventTitle,
                'event_date'     => $eventStartsAt->copy()->setTimezone($tz)->format('d/m/Y'),
                'event_time'     => $eventStartsAt->copy()->setTimezone($tz)->format('H:i'),
                'event_location' => $eventLocation,
            ]);

            EventReminder::create([
                'tenant_id'       => $tenantId,
                'lead_id'         => $leadId,
                'conversation_id' => $conversationId,
                'ai_agent_id'     => $aiAgentId,
                'google_event_id' => $googleEventId,
                'event_title'     => $eventTitle,
                'event_starts_at' => $eventStartsAt,
                'offset_minutes'  => (int) $offset,
                'send_at'         => $sendAt,
                'body'            => $body,
                'status'          => 'pending',
            ]);
        }
    }

    /**
     * Cancel all pending reminders for a Google Calendar event.
     */
    public function cancelRemindersForEvent(string $googleEventId): void
    {
        EventReminder::withoutGlobalScope('tenant')
            ->where('google_event_id', $googleEventId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Reschedule reminders when an event is moved to a new time.
     */
    public function rescheduleReminders(string $googleEventId, string $newStart): void
    {
        if (!$googleEventId || !$newStart) {
            return;
        }

        $tz = config('app.timezone', 'America/Sao_Paulo');
        $newStartsAt = Carbon::parse($newStart, $tz);

        $reminders = EventReminder::withoutGlobalScope('tenant')
            ->where('google_event_id', $googleEventId)
            ->where('status', 'pending')
            ->get();

        foreach ($reminders as $reminder) {
            $newSendAt = $newStartsAt->copy()->subMinutes($reminder->offset_minutes);

            if ($newSendAt->isPast()) {
                $reminder->update(['status' => 'cancelled']);
                continue;
            }

            $body = $this->renderTemplate(self::DEFAULT_TEMPLATE, [
                'lead_name'      => $reminder->lead?->name ?? 'Cliente',
                'event_title'    => $reminder->event_title,
                'event_date'     => $newStartsAt->copy()->setTimezone($tz)->format('d/m/Y'),
                'event_time'     => $newStartsAt->copy()->setTimezone($tz)->format('H:i'),
                'event_location' => '',
            ]);

            $reminder->update([
                'event_starts_at' => $newStartsAt,
                'send_at'         => $newSendAt,
                'body'            => $body,
            ]);
        }
    }

    private function renderTemplate(string $template, array $vars): string
    {
        return str_replace(
            ['{{lead_name}}', '{{event_title}}', '{{event_date}}', '{{event_time}}', '{{event_location}}'],
            [$vars['lead_name'], $vars['event_title'], $vars['event_date'], $vars['event_time'], $vars['event_location']],
            $template
        );
    }
}
