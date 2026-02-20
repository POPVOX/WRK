<?php

namespace App\Services;

use App\Models\Organization;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrgNameNormalizerService
{
    /**
     * How many orgs to send to Claude per batch.
     */
    protected int $batchSize = 30;

    /**
     * Find all organizations that need name normalization.
     */
    public function candidates(): Collection
    {
        return Organization::query()
            ->whereNull('suggested_name')
            ->get()
            ->filter(fn(Organization $org) => $org->needsNameNormalization());
    }

    /**
     * Normalize organization names using AI.
     *
     * @return array<int, string> Mapping of org ID → suggested name
     */
    public function normalize(Collection $orgs): array
    {
        $results = [];

        foreach ($orgs->chunk($this->batchSize) as $batch) {
            $batchResults = $this->processBatch($batch);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    /**
     * Process a single batch of orgs through Claude.
     */
    protected function processBatch(Collection $batch): array
    {
        $orgList = $batch->map(function (Organization $org) {
            $entry = "ID:{$org->id} | Current name: \"{$org->name}\"";
            if ($org->website) {
                $entry .= " | Website: {$org->website}";
            }

            return $entry;
        })->implode("\n");

        $response = AnthropicClient::send([
            'system' => 'You are an expert at identifying organizations from their domain names, URLs, and website addresses. Your job is to return the official, full organization name for each entry. If you cannot determine the name with confidence, return the best reasonable improvement (e.g. properly capitalize, add spaces). Always return proper capitalization and spacing. Never return a URL or domain as the name.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Here are organization entries that need their names normalized. Many are domain names or URLs instead of proper organization names. For each, return the proper full name of the organization.\n\nReturn your response as a JSON array of objects with \"id\" (integer) and \"name\" (string) keys. Return ONLY the JSON array, no other text.\n\n{$orgList}",
                ],
            ],
            'max_tokens' => 4000,
        ]);

        if (isset($response['error'])) {
            Log::error('OrgNameNormalizer: AI request failed', $response);

            return [];
        }

        $text = $response['content'][0]['text'] ?? '';

        return $this->parseResponse($text, $batch);
    }

    /**
     * Parse the AI response and save suggestions.
     */
    protected function parseResponse(string $text, Collection $batch): array
    {
        // Extract JSON from response (may be wrapped in code block)
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $text = trim($text);

        $parsed = json_decode($text, true);
        if (!is_array($parsed)) {
            Log::warning('OrgNameNormalizer: Could not parse AI response', ['text' => substr($text, 0, 500)]);

            return [];
        }

        $results = [];
        $batchIds = $batch->pluck('id')->toArray();

        foreach ($parsed as $item) {
            $id = $item['id'] ?? null;
            $name = trim($item['name'] ?? '');

            if (!$id || !$name || !in_array($id, $batchIds)) {
                continue;
            }

            $org = $batch->firstWhere('id', $id);
            if (!$org) {
                continue;
            }

            // Only save if the suggested name is actually different
            if (strtolower($name) !== strtolower($org->name)) {
                $org->update(['suggested_name' => $name]);
                $results[$id] = $name;
            }
        }

        return $results;
    }
}
