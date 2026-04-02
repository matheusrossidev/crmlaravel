<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\AiIntentNotification;
use App\Notifications\CampaignCompletedNotification;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\NewLeadNotification;
use App\Notifications\SystemNotification;
use App\Notifications\WhatsappConversationAssignedNotification;
use App\Notifications\LeadStageChangedNotification;
use App\Notifications\WhatsappMessageNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    /**
     * Dispatch a notification to eligible users.
     *
     * @param  string              $type         Notification type key
     * @param  array<string,mixed> $data         Event data
     * @param  int                 $tenantId     Tenant scope
     * @param  int|null            $targetUserId If set, only notify this user
     * @param  int|null            $excludeUserId Skip this user (e.g., the actor)
     */
    public function dispatch(
        string $type,
        array $data,
        int $tenantId,
        ?int $targetUserId = null,
        ?int $excludeUserId = null,
    ): void {
        try {
            $notification = $this->createNotification($type, $data);
            if (! $notification) {
                return;
            }

            $users = $this->getEligibleUsers($tenantId, $targetUserId, $excludeUserId);

            foreach ($users as $user) {
                if ($user->isInQuietHours()) {
                    continue;
                }

                if (! $user->wantsNotification($type, 'browser')
                    && ! $user->wantsNotification($type, 'push')) {
                    continue;
                }

                // Group similar unread notifications (avoid spam)
                $existing = $user->unreadNotifications()
                    ->where('type', get_class($notification))
                    ->where('created_at', '>=', now()->subHours(2))
                    ->first();

                if ($existing) {
                    $existingData = $existing->data;
                    $existingData['count'] = ($existingData['count'] ?? 1) + 1;
                    $existingData['last_item'] = $data;
                    $existing->update(['data' => $existingData, 'read_at' => null]);
                    continue;
                }

                $user->notify($notification);
            }
        } catch (\Throwable $e) {
            Log::warning('NotificationDispatcher error: ' . $e->getMessage(), [
                'type' => $type,
                'tenant_id' => $tenantId,
            ]);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function getEligibleUsers(int $tenantId, ?int $targetUserId, ?int $excludeUserId)
    {
        $query = User::where('tenant_id', $tenantId);

        if ($targetUserId) {
            $query->where('id', $targetUserId);
        }

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->get();
    }

    private function createNotification(string $type, array $data): ?Notification
    {
        return match ($type) {
            'new_lead' => new NewLeadNotification(
                $data['lead_name'] ?? 'Novo Lead',
                $data['url'] ?? null,
            ),
            'lead_assigned' => new LeadAssignedNotification(
                $data['lead_name'] ?? 'Lead',
                $data['assigned_by'] ?? 'alguém',
                $data['url'] ?? null,
            ),
            'whatsapp_message' => new WhatsappMessageNotification(
                $data['contact_name'] ?? 'Contato',
                $data['message_preview'] ?? '',
                $data['url'] ?? null,
            ),
            'whatsapp_assigned' => new WhatsappConversationAssignedNotification(
                $data['contact_name'] ?? 'Contato',
                $data['assigned_by'] ?? 'alguém',
                $data['url'] ?? null,
            ),
            'ai_intent' => new AiIntentNotification(
                $data['contact_name'] ?? 'Contato',
                $data['intent_type'] ?? 'compra',
                $data['url'] ?? null,
            ),
            'campaign_completed' => new CampaignCompletedNotification(
                $data['campaign_name'] ?? 'Campanha',
                $data['url'] ?? null,
            ),
            'lead_stage_changed' => new LeadStageChangedNotification(
                $data['lead_name'] ?? 'Lead',
                $data['stage_name'] ?? '',
                $data['url'] ?? null,
            ),
            'master_notification' => new SystemNotification(
                $data['title'] ?? 'Notificação',
                $data['body'] ?? '',
                $data['url'] ?? null,
            ),
            default => null,
        };
    }
}
