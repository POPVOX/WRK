<?php

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifications')]
class NotificationsIndex extends Component
{
    public string $scope = 'all';

    public int $limit = 50;

    public function setScope(string $scope): void
    {
        $this->scope = in_array($scope, ['unread', 'all'], true) ? $scope : 'all';
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = $this->findNotification($notificationId);
        if (! $notification || $notification->read_at !== null) {
            return;
        }

        $notification->markAsRead();
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

    public function loadMore(): void
    {
        $this->limit = min(500, $this->limit + 50);
    }

    public function getUnreadCountProperty(): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    public function getItemsProperty(): Collection
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
            'action_url' => $payload['action_url'] ?? null,
            'action_label' => (string) ($payload['action_label'] ?? 'Open'),
            'is_read' => $notification->read_at !== null,
            'time_label' => optional($notification->created_at)->diffForHumans() ?? '',
        ];
    }

    public function render()
    {
        return view('livewire.notifications.notifications-index', [
            'items' => $this->items,
            'unreadCount' => $this->unreadCount,
        ]);
    }
}
