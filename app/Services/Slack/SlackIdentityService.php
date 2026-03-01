<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Models\UserSlackIdentity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class SlackIdentityService
{
    public function resolveUser(string $slackUserId, ?string $workspaceId = null): ?User
    {
        if (! Schema::hasTable('user_slack_identities')) {
            return null;
        }

        $normalizedSlackUserId = $this->normalizeSlackUserId($slackUserId);
        $normalizedWorkspaceId = $this->normalizeWorkspaceId($workspaceId);
        if ($normalizedSlackUserId === '' || $normalizedWorkspaceId === '') {
            return null;
        }

        $identity = UserSlackIdentity::query()
            ->where('workspace_id', $normalizedWorkspaceId)
            ->where('slack_user_id', $normalizedSlackUserId)
            ->first();

        return $identity?->user;
    }

    public function rememberIdentity(User $user, string $slackUserId, ?string $workspaceId = null): ?UserSlackIdentity
    {
        if (! Schema::hasTable('user_slack_identities')) {
            return null;
        }

        $normalizedSlackUserId = $this->normalizeSlackUserId($slackUserId);
        $normalizedWorkspaceId = $this->normalizeWorkspaceId($workspaceId);
        if ($normalizedSlackUserId === '' || $normalizedWorkspaceId === '') {
            return null;
        }

        return UserSlackIdentity::query()->updateOrCreate(
            [
                'workspace_id' => $normalizedWorkspaceId,
                'slack_user_id' => $normalizedSlackUserId,
            ],
            [
                'user_id' => $user->id,
            ]
        );
    }

    protected function normalizeSlackUserId(string $slackUserId): string
    {
        return Str::upper(trim($slackUserId));
    }

    protected function normalizeWorkspaceId(?string $workspaceId): string
    {
        $candidate = trim((string) ($workspaceId ?? ''));
        if ($candidate === '') {
            $candidate = trim((string) config('services.slack.workspace_id', ''));
        }

        return Str::upper($candidate);
    }
}
