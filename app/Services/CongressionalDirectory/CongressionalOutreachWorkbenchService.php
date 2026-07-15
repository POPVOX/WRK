<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachEmailSuppression;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CongressionalOutreachWorkbenchService
{
    public function __construct(
        protected CongressionalEmailEligibilityService $eligibility
    ) {}

    public function createDraft(CongressionalStaffList $list, int $userId, string $name): CongressionalOutreachDraft
    {
        if (! $list->profiles()->exists()) {
            throw new DomainException('Add at least one staff member to the list before creating a dry run.');
        }

        return DB::transaction(function () use ($list, $userId, $name) {
            $draft = CongressionalOutreachDraft::query()->create([
                'congressional_staff_list_id' => $list->id,
                'user_id' => $userId,
                'name' => trim($name),
                'status' => 'building',
            ]);

            return $draft->fresh();
        });
    }

    public function refreshSnapshot(CongressionalOutreachDraft $draft): int
    {
        return DB::transaction(function () use ($draft) {
            $draft->recipients()->delete();

            $profiles = $draft->staffList->profiles()
                ->with(['currentPosition.office', 'emails'])
                ->orderBy('display_name')
                ->get();

            $suppressions = OutreachEmailSuppression::query()
                ->whereIn(
                    'email_normalized',
                    $profiles->flatMap->emails->pluck('email_normalized')->filter()->unique()
                )
                ->get()
                ->keyBy('email_normalized');

            $now = now();
            $rows = $profiles
                ->map(fn (CongressionalStaffProfile $profile) => $this->snapshotProfile($draft, $profile, $suppressions, $now))
                ->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('congressional_outreach_draft_recipients')->insert($chunk);
            }

            $this->reconcileDuplicates($draft, resetExisting: false);
            $metadata = $draft->metadata ?? [];
            unset($metadata['snapshot_error'], $metadata['snapshot_failed_at']);
            $draft->update([
                'status' => 'draft',
                'snapshot_at' => $now,
                'reviewed_at' => null,
                'metadata' => $metadata ?: null,
            ]);

            return $profiles->count();
        });
    }

    public function selectEmail(
        CongressionalOutreachDraftRecipient $recipient,
        CongressionalStaffEmail $staffEmail
    ): void {
        if (! $recipient->profile_id || $staffEmail->profile_id !== $recipient->profile_id) {
            throw new DomainException('That address does not belong to this staff profile.');
        }

        DB::transaction(function () use ($recipient, $staffEmail) {
            $profile = $recipient->profile()->with('currentPosition')->first();
            $evaluation = $this->eligibility->evaluate($staffEmail);
            $baseExclusion = $this->baseExclusionReason($profile, $evaluation['tier'], $staffEmail);
            $metadata = $recipient->metadata ?? [];
            $metadata['base_exclusion_reason'] = $baseExclusion;

            $recipient->update([
                'staff_email_id' => $staffEmail->id,
                'email' => $staffEmail->email,
                'email_normalized' => $staffEmail->email_normalized,
                'eligibility_tier' => $evaluation['tier'],
                'source_type' => $staffEmail->source_type,
                'verification_status' => $staffEmail->verification_status,
                'review_status' => $baseExclusion ? 'excluded' : 'pending',
                'exclusion_reason' => $baseExclusion,
                'selection_reason' => $evaluation['reason'],
                'metadata' => $metadata,
                'approved_by' => null,
                'reviewed_at' => null,
            ]);

            $this->reconcileDuplicates($recipient->draft);
            $this->reopen($recipient->draft);
        });
    }

    public function approve(CongressionalOutreachDraftRecipient $recipient, int $userId): void
    {
        if ($recipient->exclusion_reason
            || ! in_array($recipient->eligibility_tier, ['eligible', 'limited'], true)
            || ! $recipient->staff_email_id) {
            throw new DomainException('This recipient is not available for approval.');
        }

        $recipient->update([
            'review_status' => 'approved',
            'approved_by' => $userId,
            'reviewed_at' => now(),
        ]);
        $this->reopen($recipient->draft);
    }

    public function approveAllEligible(CongressionalOutreachDraft $draft, int $userId): int
    {
        $count = $draft->recipients()
            ->where('review_status', 'pending')
            ->whereNull('exclusion_reason')
            ->where('eligibility_tier', 'eligible')
            ->update([
                'review_status' => 'approved',
                'approved_by' => $userId,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $this->reopen($draft);

        return $count;
    }

    public function exclude(CongressionalOutreachDraftRecipient $recipient, int $userId): void
    {
        $recipient->update([
            'review_status' => 'excluded',
            'exclusion_reason' => 'manual_exclusion',
            'approved_by' => $userId,
            'reviewed_at' => now(),
        ]);
        $this->reopen($recipient->draft);
    }

    public function restore(CongressionalOutreachDraftRecipient $recipient): void
    {
        if ($recipient->exclusion_reason !== 'manual_exclusion') {
            throw new DomainException('Only manually excluded recipients can be restored here.');
        }

        $baseExclusion = data_get($recipient->metadata, 'base_exclusion_reason');
        $recipient->update([
            'review_status' => $baseExclusion ? 'excluded' : 'pending',
            'exclusion_reason' => $baseExclusion,
            'approved_by' => null,
            'reviewed_at' => null,
        ]);

        $this->reconcileDuplicates($recipient->draft);
        $this->reopen($recipient->draft);
    }

    public function updateMessage(CongressionalOutreachDraft $draft, ?string $subject, ?string $bodyText): void
    {
        $draft->update([
            'subject' => trim((string) $subject) ?: null,
            'body_text' => trim((string) $bodyText) ?: null,
            'status' => 'draft',
            'reviewed_at' => null,
        ]);
    }

    public function markReady(CongressionalOutreachDraft $draft): void
    {
        if (! trim((string) $draft->subject) || ! trim((string) $draft->body_text)) {
            throw new DomainException('Add a subject and message before marking the dry run ready.');
        }

        if (! $draft->recipients()->where('review_status', 'approved')->exists()) {
            throw new DomainException('Approve at least one recipient before marking the dry run ready.');
        }

        if ($draft->recipients()->where('review_status', 'pending')->exists()) {
            throw new DomainException('Approve or exclude every pending recipient first.');
        }

        $draft->update([
            'status' => 'ready',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * @return array{subject:string,body:string}
     */
    public function preview(CongressionalOutreachDraft $draft, CongressionalOutreachDraftRecipient $recipient): array
    {
        $firstName = Str::before(trim($recipient->name), ' ');
        $replacements = [
            '{{first_name}}' => $firstName,
            '{{name}}' => $recipient->name,
            '{{title}}' => (string) $recipient->title,
            '{{office}}' => (string) $recipient->office,
        ];

        return [
            'subject' => strtr((string) $draft->subject, $replacements),
            'body' => strtr((string) $draft->body_text, $replacements),
        ];
    }

    /**
     * @return array{total:int,approved:int,pending:int,excluded:int,eligible:int,limited:int,blocked:int,unavailable:int,suppressed:int}
     */
    public function summary(CongressionalOutreachDraft $draft): array
    {
        $counts = $draft->recipients()
            ->selectRaw('review_status, eligibility_tier, exclusion_reason, COUNT(*) as aggregate')
            ->groupBy('review_status', 'eligibility_tier', 'exclusion_reason')
            ->get();

        return [
            'total' => $counts->sum('aggregate'),
            'approved' => $counts->where('review_status', 'approved')->sum('aggregate'),
            'pending' => $counts->where('review_status', 'pending')->sum('aggregate'),
            'excluded' => $counts->where('review_status', 'excluded')->sum('aggregate'),
            'eligible' => $counts->where('eligibility_tier', 'eligible')->sum('aggregate'),
            'limited' => $counts->where('eligibility_tier', 'limited')->sum('aggregate'),
            'blocked' => $counts->where('eligibility_tier', 'blocked')->sum('aggregate'),
            'unavailable' => $counts->whereIn('exclusion_reason', ['no_address', 'inactive_profile'])->sum('aggregate'),
            'suppressed' => $counts->where('exclusion_reason', 'blocked_address')->sum('aggregate'),
        ];
    }

    /**
     * @param  Collection<string,OutreachEmailSuppression>  $suppressions
     * @return array<string,mixed>
     */
    protected function snapshotProfile(
        CongressionalOutreachDraft $draft,
        CongressionalStaffProfile $profile,
        Collection $suppressions,
        mixed $now
    ): array {
        $position = $profile->currentPosition;
        $preferred = $this->preferredEmail($profile->emails, $suppressions);
        /** @var CongressionalStaffEmail|null $staffEmail */
        $staffEmail = $preferred['email'] ?? null;
        $evaluation = $preferred['evaluation'] ?? [
            'tier' => 'blocked',
            'reason' => 'No email address is available.',
        ];
        $baseExclusion = $this->baseExclusionReason($profile, $evaluation['tier'], $staffEmail);

        return [
            'draft_id' => $draft->id,
            'profile_id' => $profile->id,
            'staff_email_id' => $staffEmail?->id,
            'email' => $staffEmail?->email,
            'email_normalized' => $staffEmail?->email_normalized,
            'name' => $profile->display_name,
            'title' => $position?->title,
            'office' => $position?->office?->name,
            'eligibility_tier' => $evaluation['tier'],
            'source_type' => $staffEmail?->source_type,
            'verification_status' => $staffEmail?->verification_status,
            'review_status' => $baseExclusion ? 'excluded' : 'pending',
            'exclusion_reason' => $baseExclusion,
            'selection_reason' => $evaluation['reason'],
            'metadata' => json_encode([
                'base_exclusion_reason' => $baseExclusion,
                'available_email_count' => $profile->emails->count(),
            ], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  Collection<int,CongressionalStaffEmail>  $emails
     * @param  Collection<string,OutreachEmailSuppression>  $suppressions
     * @return array{email:CongressionalStaffEmail,evaluation:array<string,mixed>}|null
     */
    protected function preferredEmail(Collection $emails, Collection $suppressions): ?array
    {
        return $emails
            ->map(fn (CongressionalStaffEmail $email) => [
                'email' => $email,
                'evaluation' => $this->eligibility->evaluateWithSuppression(
                    $email,
                    $suppressions->get($email->email_normalized)
                ),
            ])
            ->sort(function (array $left, array $right): int {
                /** @var CongressionalStaffEmail $leftEmail */
                $leftEmail = $left['email'];
                /** @var CongressionalStaffEmail $rightEmail */
                $rightEmail = $right['email'];
                $leftRank = [
                    $this->tierRank($left['evaluation']['tier']),
                    $leftEmail->is_primary ? 0 : 1,
                    $this->verificationRank($leftEmail->verification_status),
                    -($leftEmail->last_observed_at?->timestamp ?? 0),
                    $leftEmail->id,
                ];
                $rightRank = [
                    $this->tierRank($right['evaluation']['tier']),
                    $rightEmail->is_primary ? 0 : 1,
                    $this->verificationRank($rightEmail->verification_status),
                    -($rightEmail->last_observed_at?->timestamp ?? 0),
                    $rightEmail->id,
                ];

                return $leftRank <=> $rightRank;
            })
            ->first();
    }

    protected function baseExclusionReason(
        ?CongressionalStaffProfile $profile,
        string $tier,
        ?CongressionalStaffEmail $staffEmail
    ): ?string {
        if (! $profile?->currentPosition) {
            return 'inactive_profile';
        }

        if (! $staffEmail) {
            return 'no_address';
        }

        if ($tier === 'blocked') {
            return 'blocked_address';
        }

        return null;
    }

    protected function reconcileDuplicates(CongressionalOutreachDraft $draft, bool $resetExisting = true): void
    {
        if ($resetExisting) {
            $recipients = $draft->recipients()->orderBy('id')->get();

            foreach ($recipients as $recipient) {
                if ($recipient->exclusion_reason === 'manual_exclusion') {
                    continue;
                }

                $baseExclusion = data_get($recipient->metadata, 'base_exclusion_reason');
                $recipient->update([
                    'review_status' => $baseExclusion ? 'excluded' : ($recipient->review_status === 'approved' ? 'approved' : 'pending'),
                    'exclusion_reason' => $baseExclusion,
                    'approved_by' => $baseExclusion ? null : $recipient->approved_by,
                    'reviewed_at' => $baseExclusion ? null : $recipient->reviewed_at,
                ]);
            }
        }

        $duplicateEmails = $draft->recipients()
            ->whereNotNull('email_normalized')
            ->whereNull('exclusion_reason')
            ->select('email_normalized')
            ->groupBy('email_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email_normalized');

        $draft->recipients()
            ->whereIn('email_normalized', $duplicateEmails)
            ->whereNull('exclusion_reason')
            ->get()
            ->groupBy('email_normalized')
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->each(function (Collection $group): void {
                $canonical = $group
                    ->sortBy(fn (CongressionalOutreachDraftRecipient $recipient) => [
                        $recipient->review_status === 'approved' ? 0 : 1,
                        $this->tierRank($recipient->eligibility_tier),
                        $recipient->id,
                    ])
                    ->first();

                $group->where('id', '!=', $canonical->id)->each(function (CongressionalOutreachDraftRecipient $duplicate): void {
                    $duplicate->update([
                        'review_status' => 'excluded',
                        'exclusion_reason' => 'duplicate_address',
                        'approved_by' => null,
                        'reviewed_at' => null,
                    ]);
                });
            });
    }

    protected function reopen(CongressionalOutreachDraft $draft): void
    {
        if ($draft->status !== 'draft' || $draft->reviewed_at) {
            $draft->update(['status' => 'draft', 'reviewed_at' => null]);
        }
    }

    protected function tierRank(string $tier): int
    {
        return match ($tier) {
            'eligible' => 0,
            'limited' => 1,
            default => 2,
        };
    }

    protected function verificationRank(string $status): int
    {
        return match ($status) {
            'confirmed' => 0,
            'replied' => 1,
            'observed' => 2,
            'sourced' => 3,
            'not_bounced' => 4,
            'unverified' => 5,
            default => 6,
        };
    }
}
