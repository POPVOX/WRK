<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\WorkspaceAlert;
use Illuminate\Support\Collection;

class WorkspaceNotificationService
{
    public const LEVELS = ['info', 'success', 'warning', 'urgent'];

    /**
     * @param  iterable<User>  $users
     */
    public function sendToUsers(
        iterable $users,
        string $kind,
        string $title,
        string $body,
        array $options = [],
    ): int {
        $recipients = collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique(fn (User $user) => $user->id)
            ->values();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $level = in_array(($options['level'] ?? 'info'), self::LEVELS, true)
            ? (string) $options['level']
            : 'info';

        $actor = ($options['actor'] ?? null) instanceof User ? $options['actor'] : null;
        $payload = [
            'kind' => $kind,
            'category' => (string) ($options['category'] ?? 'general'),
            'level' => $level,
            'title' => $title,
            'body' => $body,
            'action_url' => $options['action_url'] ?? null,
            'action_label' => $options['action_label'] ?? null,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'manual' => (bool) ($options['manual'] ?? false),
            'happened_at' => now()->toIso8601String(),
            'meta' => is_array($options['meta'] ?? null) ? $options['meta'] : [],
        ];

        $sent = 0;

        /** @var User $recipient */
        foreach ($recipients as $recipient) {
            $recipient->notify(new WorkspaceAlert($payload));
            $sent++;
        }

        return $sent;
    }

    public function resolveAudience(string $audience, array $selectedUserIds = []): Collection
    {
        return match ($audience) {
            'management' => User::query()
                ->where(function ($query) {
                    $query->where('is_admin', true)
                        ->orWhereIn('access_level', ['management', 'admin']);
                })
                ->where('is_visible', true)
                ->orderBy('name')
                ->get(),
            'admins' => User::query()
                ->where(function ($query) {
                    $query->where('is_admin', true)
                        ->orWhere('access_level', 'admin');
                })
                ->where('is_visible', true)
                ->orderBy('name')
                ->get(),
            'specific_users' => empty($selectedUserIds)
                ? collect()
                : User::query()
                    ->whereIn('id', $selectedUserIds)
                    ->where('is_visible', true)
                    ->orderBy('name')
                    ->get(),
            default => User::query()
                ->where('is_visible', true)
                ->orderBy('name')
                ->get(),
        };
    }
}

