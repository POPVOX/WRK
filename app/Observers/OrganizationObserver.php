<?php

namespace App\Observers;

use App\Models\Organization;
use App\Services\LinkedInScraperService;
use Illuminate\Support\Facades\Log;

class OrganizationObserver
{
    public function __construct(
        protected LinkedInScraperService $linkedInService
    ) {
    }

    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        $this->syncLinkedInIfNeeded($organization);
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        // Only sync LinkedIn if the URL was just added or changed
        if ($organization->wasChanged('linkedin_url') && $organization->linkedin_url) {
            $this->syncLinkedInIfNeeded($organization);
        }
    }

    /**
     * Sync LinkedIn data if a LinkedIn URL is present.
     */
    protected function syncLinkedInIfNeeded(Organization $organization): void
    {
        if (empty($organization->linkedin_url)) {
            return;
        }

        try {
            Log::info("Syncing LinkedIn for Organization: {$organization->name} ({$organization->linkedin_url})");

            $data = $this->linkedInService->extractCompanyData($organization->linkedin_url);

            $updates = [];

            // Update logo if not already set
            if (empty($organization->logo_url) && !empty($data['logo_url'])) {
                $updates['logo_url'] = $data['logo_url'];
            }

            // Update description if not already set
            if (empty($organization->description) && !empty($data['description'])) {
                $updates['description'] = $data['description'];
            }

            if (!empty($updates)) {
                // Use query builder to avoid triggering observer again
                Organization::where('id', $organization->id)->update($updates);
                Log::info("LinkedIn sync updated Organization {$organization->id}: " . json_encode($updates));
            }
        } catch (\Exception $e) {
            Log::error("LinkedIn sync failed for Organization {$organization->id}: " . $e->getMessage());
        }
    }
}
