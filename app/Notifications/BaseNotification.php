<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

abstract class BaseNotification extends Notification
{
    use Queueable;

    protected string $notificationType;
    protected string $title;
    protected string $body;
    protected ?string $url;
    protected ?string $icon;

    public function __construct(string $title, string $body, ?string $url = null, ?string $icon = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->icon = $icon;
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsNotification($this->notificationType, 'browser')) {
            $channels[] = 'broadcast';
        }

        if ($notifiable->wantsNotification($this->notificationType, 'push')
            && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'notification_type' => $this->notificationType,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'notification_type' => $this->notificationType,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ]);
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon ?? '/images/favicon.svg')
            ->badge('/images/favicon.png')
            ->data(['url' => $this->url ?? '/'])
            ->tag($this->notificationType);
    }

    public function broadcastType(): string
    {
        return 'notification.' . $this->notificationType;
    }
}
