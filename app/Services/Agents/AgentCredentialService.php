<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentCredential;
use App\Services\Box\BoxClient;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use RuntimeException;

class AgentCredentialService
{
    /**
     * @param  array<string,mixed>  $tokenData
     * @param  array<int,string>  $scopes
     */
    public function storeCredential(
        Agent $agent,
        string $service,
        array $tokenData,
        array $scopes = [],
        ?CarbonInterface $expiresAt = null
    ): AgentCredential {
        $normalizedService = $this->normalizeService($service);

        $cleanScopes = collect($scopes)
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return AgentCredential::query()->updateOrCreate(
            [
                'agent_id' => $agent->id,
                'service' => $normalizedService,
            ],
            [
                'token_data' => $tokenData,
                'scopes' => $cleanScopes,
                'expires_at' => $expiresAt,
                'refreshed_at' => now(),
            ]
        );
    }

    public function getCredential(Agent $agent, string $service): ?AgentCredential
    {
        return AgentCredential::query()
            ->where('agent_id', $agent->id)
            ->where('service', $this->normalizeService($service))
            ->first();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getTokenData(Agent $agent, string $service): ?array
    {
        $credential = $this->getCredential($agent, $service);
        if (! $credential) {
            return null;
        }

        return is_array($credential->token_data) ? $credential->token_data : null;
    }

    /**
     * @return array<string,mixed>
     */
    public function requireTokenData(Agent $agent, string $service): array
    {
        $tokenData = $this->getTokenData($agent, $service);
        if (! is_array($tokenData) || trim((string) ($tokenData['access_token'] ?? '')) === '') {
            $normalizedService = $this->normalizeService($service);
            throw new RuntimeException("Missing {$normalizedService} credentials for agent {$agent->id}.");
        }

        return $tokenData;
    }

    public function getAccessToken(Agent $agent, string $service): ?string
    {
        $tokenData = $this->getTokenData($agent, $service);
        if (! is_array($tokenData)) {
            return null;
        }

        $accessToken = trim((string) ($tokenData['access_token'] ?? ''));

        return $accessToken !== '' ? $accessToken : null;
    }

    public function boxClientForAgent(Agent $agent, BoxClient $boxClient): BoxClient
    {
        $accessToken = $this->getAccessToken($agent, AgentCredential::SERVICE_BOX);
        if ($accessToken === null) {
            return $boxClient;
        }

        return $boxClient->withAccessToken($accessToken);
    }

    protected function normalizeService(string $service): string
    {
        $normalized = Str::lower(trim($service));
        $allowed = [
            AgentCredential::SERVICE_GMAIL,
            AgentCredential::SERVICE_BOX,
            AgentCredential::SERVICE_GCAL,
            AgentCredential::SERVICE_SLACK,
        ];

        if (! in_array($normalized, $allowed, true)) {
            throw new RuntimeException("Unsupported credential service [{$service}].");
        }

        return $normalized;
    }
}
