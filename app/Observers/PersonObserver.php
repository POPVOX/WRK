<?php

namespace App\Observers;

use App\Models\Person;
use App\Services\LinkedInScraperService;
use Illuminate\Support\Facades\Log;

class PersonObserver
{
    public function __construct(
        protected LinkedInScraperService $linkedInService
    ) {}

    /**
     * Handle the Person "created" event.
     */
    public function created(Person $person): void
    {
        $this->syncLinkedInIfNeeded($person);
    }

    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void
    {
        // Only sync LinkedIn if the URL was just added or changed
        if ($person->wasChanged('linkedin_url') && $person->linkedin_url) {
            $this->syncLinkedInIfNeeded($person);
        }
    }

    /**
     * Sync LinkedIn data if a LinkedIn URL is present.
     */
    protected function syncLinkedInIfNeeded(Person $person): void
    {
        if (empty($person->linkedin_url)) {
            return;
        }

        try {
            Log::info("Syncing LinkedIn for Person: {$person->name} ({$person->linkedin_url})");

            $data = $this->linkedInService->extractCompanyData($person->linkedin_url);

            $updates = [];

            // Update photo if not already set
            if (empty($person->photo_url) && ! empty($data['logo_url'])) {
                $updates['photo_url'] = $data['logo_url'];
            }

            // Update title if not already set
            if (empty($person->title) && ! empty($data['job_title'])) {
                $updates['title'] = $data['job_title'];
            }

            // Update bio if not already set
            if (empty($person->bio) && ! empty($data['description'])) {
                $updates['bio'] = $data['description'];
            }

            if (! empty($updates)) {
                // Use query builder to avoid triggering observer again
                Person::where('id', $person->id)->update($updates);
                Log::info("LinkedIn sync updated Person {$person->id}: ".json_encode($updates));
            }
        } catch (\Exception $e) {
            Log::error("LinkedIn sync failed for Person {$person->id}: ".$e->getMessage());
        }
    }
}
