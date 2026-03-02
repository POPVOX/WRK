<?php

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsPanel extends Component
{
    public bool $open = false;

    public string $scope = 'unread';

    public int $limit = 25;

    public function togglePanel(): void
    {
        $this->open = ! $this->open;
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function setScope(string $scope): void
    {
        $this->scope = in_array($scope, ['unread', 'all'], true) ? $scope : 'unread';
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = $this->findNotification($notificationId);
        if (! $notification) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAsUnread(string $notificationId): void
    {
        $notification = $this->findNotification($notificationId);
        if (! $notification) {
            return;
        }

        $notification->update(['read_at' => null]);
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function getUnreadCountProperty(): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    public function getNotificationsProperty(): Collection
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        $query = $this->scope === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        return $query
            ->latest()
            ->limit($this->limit)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->mapNotification($notification));
    }

    protected function findNotification(string $notificationId): ?DatabaseNotification
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $user->notifications()->whereKey($notificationId)->first();
    }

    protected function mapNotification(DatabaseNotification $notification): array
    {
        $payload = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => (string) ($payload['title'] ?? 'Notification'),
            'body' => (string) ($payload['body'] ?? ''),
            'level' => (string) ($payload['level'] ?? 'info'),
            'kind' => (string) ($payload['kind'] ?? 'general'),
            'category' => (string) ($payload['category'] ?? 'general'),
            'action_url' => $payload['action_url'] ?? null,
            'action_label' => (string) ($payload['action_label'] ?? 'Open'),
            'actor_name' => $payload['actor_name'] ?? null,
            'is_read' => $notification->read_at !== null,
            'time_label' => optional($notification->created_at)->diffForHumans() ?? '',
        ];
    }

    public function render()
    {
        return view('livewire.notifications.notifications-panel', [
            'unreadCount' => $this->unreadCount,
            'items' => $this->notifications,
        ]);
    }
}

