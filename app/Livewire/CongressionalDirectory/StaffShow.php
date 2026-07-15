<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachEmailSuppression;
use App\Services\CongressionalDirectory\CongressionalEmailEligibilityService;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Congressional Staff Profile')]
class StaffShow extends Component
{
    public CongressionalStaffProfile $profile;

    public string $emailAddress = '';

    public string $emailSourceType = 'guessed';

    public string $emailSourceUrl = '';

    public string $emailSourceNotes = '';

    public function mount(CongressionalStaffProfile $profile): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);

        $this->profile = $profile;
    }

    public function addEmail(CongressionalEmailEvidenceService $evidence): void
    {
        $validated = $this->validate([
            'emailAddress' => ['required', 'email:rfc', 'max:255'],
            'emailSourceType' => ['required', Rule::in(CongressionalEmailEvidenceService::SOURCE_TYPES)],
            'emailSourceUrl' => ['nullable', 'url:http,https', 'max:2000'],
            'emailSourceNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $evidence->addAddress(
            $this->profile,
            $validated['emailAddress'],
            $validated['emailSourceType'],
            auth()->id(),
            trim($validated['emailSourceUrl']) ?: null,
            trim($validated['emailSourceNotes']) ?: null
        );

        $this->reset(['emailAddress', 'emailSourceUrl', 'emailSourceNotes']);
        $this->emailSourceType = 'guessed';
        $this->dispatch('notify', type: 'success', message: 'Email evidence added. No message has been sent.');
    }

    public function markEmailConfirmed(int $staffEmailId, CongressionalEmailEvidenceService $evidence): void
    {
        $staffEmail = $this->staffEmail($staffEmailId);
        $evidence->recordEvent(
            $staffEmail,
            'confirmed',
            userId: auth()->id(),
            evidenceExcerpt: 'Confirmed manually by a team member.'
        );
        $this->dispatch('notify', type: 'success', message: 'Email marked confirmed.');
    }

    public function suppressEmail(int $staffEmailId, CongressionalEmailEvidenceService $evidence): void
    {
        $evidence->suppressManually($this->staffEmail($staffEmailId), auth()->id());
        $this->dispatch('notify', type: 'success', message: 'Email suppressed from outreach.');
    }

    public function restoreEmail(int $staffEmailId, CongressionalEmailEvidenceService $evidence): void
    {
        $restored = $evidence->restoreManualSuppression($this->staffEmail($staffEmailId), auth()->id());
        $this->dispatch(
            'notify',
            type: $restored ? 'success' : 'error',
            message: $restored ? 'Manual suppression removed.' : 'Only manual suppressions can be restored here.'
        );
    }

    protected function staffEmail(int $staffEmailId): CongressionalStaffEmail
    {
        return $this->profile->emails()->findOrFail($staffEmailId);
    }

    public function render(CongressionalEmailEligibilityService $eligibility)
    {
        $this->profile->load([
            'person',
            'positions' => fn ($query) => $query
                ->with('office')
                ->orderByDesc('is_current')
                ->orderByDesc('last_reported_end'),
            'observations' => fn ($query) => $query
                ->with('office')
                ->orderByDesc('period_end')
                ->limit(50),
            'emails' => fn ($query) => $query
                ->with(['events' => fn ($query) => $query->latest('occurred_at')->limit(10)])
                ->orderByDesc('is_primary')
                ->orderBy('email_normalized'),
        ]);

        return view('livewire.congressional-directory.staff-show', [
            'emailEligibility' => $this->profile->emails
                ->mapWithKeys(fn (CongressionalStaffEmail $staffEmail) => [
                    $staffEmail->id => $eligibility->evaluate($staffEmail),
                ]),
            'suppressionReasons' => OutreachEmailSuppression::query()
                ->whereIn('email_normalized', $this->profile->emails->pluck('email_normalized'))
                ->pluck('reason', 'email_normalized'),
        ]);
    }
}
