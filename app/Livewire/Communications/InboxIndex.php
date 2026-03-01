<?php

namespace App\Livewire\Communications;

use App\Jobs\SyncGmailMessages;
use App\Models\Action;
use App\Models\GmailMessage;
use App\Models\Grant;
use App\Models\InboxActionLog;
use App\Models\InboxThreadContextLink;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonInteraction;
use App\Models\Project;
use App\Services\GoogleGmailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Google\Service\Gmail as GoogleGmail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Inbox')]
class InboxIndex extends Component
{
    #[Url(as: 'folder')]
    public string $folder = 'inbox';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'thread')]
    public ?string $selectedThreadKey = null;

    public string $selectedProjectId = '';

    public string $selectedGrantId = '';

    public string $selectedContextPersonId = '';

    public string $selectedContextOrganizationId = '';

    public string $selectedContextMeetingId = '';

    public string $selectedContextMediaContactId = '';

    public string $selectedContextMediaOutletId = '';

    public string $replyDraft = '';

    public bool $showComposer = false;

    public string $composeTo = '';

    public string $composeCc = '';

    public string $composeBcc = '';

    public string $composeSubject = '';

    public string $composeBody = '';

    public string $composeStatus = '';

    public string $composeStatusType = 'info';

    public bool $isSyncingGmail = false;

    public ?string $lastGmailSyncAt = null;

    public bool $readOnlyMode = false;

    public array $folders = [
        'inbox' => 'Inbox',
        'sent' => 'Sent',
        'drafts' => 'Drafts',
        'archive' => 'Archive',
        'all' => 'All Mail',
    ];

    public function mount(): void
    {
        $this->normalizeFolder();

        $user = Auth::user();
        $this->lastGmailSyncAt = $user?->gmail_import_date?->diffForHumans();
        $this->readOnlyMode = ! $this->gmailWriteScopesConfigured();
    }

    public function updatedFolder(): void
    {
        $this->normalizeFolder();
        $this->selectedThreadKey = null;
        $this->selectedProjectId = '';
        $this->selectedGrantId = '';
        $this->selectedContextPersonId = '';
        $this->selectedContextOrganizationId = '';
        $this->selectedContextMeetingId = '';
        $this->selectedContextMediaContactId = '';
        $this->selectedContextMediaOutletId = '';
        $this->replyDraft = '';
    }

    public function updatedSearch(): void
    {
        $this->selectedThreadKey = null;
        $this->selectedProjectId = '';
        $this->selectedGrantId = '';
        $this->selectedContextPersonId = '';
        $this->selectedContextOrganizationId = '';
        $this->selectedContextMeetingId = '';
        $this->selectedContextMediaContactId = '';
        $this->selectedContextMediaOutletId = '';
        $this->replyDraft = '';
    }

    public function selectThread(string $threadKey): void
    {
        $this->selectedThreadKey = $threadKey;
        $this->selectedProjectId = '';
        $this->selectedGrantId = '';
        $this->selectedContextPersonId = '';
        $this->selectedContextOrganizationId = '';
        $this->selectedContextMeetingId = '';
        $this->selectedContextMediaContactId = '';
        $this->selectedContextMediaOutletId = '';
        $this->replyDraft = '';
    }

    public function openComposer(): void
    {
        $this->clearComposeStatus();
        $this->showComposer = true;
        $this->composeTo = '';
        $this->composeCc = '';
        $this->composeBcc = '';
        $this->composeSubject = '';
        $this->composeBody = '';
    }

    public function openComposerForReply(): void
    {
        $this->clearComposeStatus();
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $to = trim((string) ($snapshot['counterpart_email'] ?? ''));
        if ($to === '') {
            $this->dispatch('notify', type: 'error', message: 'No recipient email found on this thread.');

            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'Reply');
        if (! str_starts_with(Str::lower($subject), 're:')) {
            $subject = 'Re: '.$subject;
        }

        $this->showComposer = true;
        $this->composeTo = $to;
        $this->composeCc = '';
        $this->composeBcc = '';
        $this->composeSubject = $subject;
        $this->composeBody = $this->replyDraft !== '' ? $this->replyDraft : '';
    }

    public function openComposerForReplyAll(): void
    {
        $this->clearComposeStatus();
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        $latest = $this->selectedLatestMessageModel();
        if (! $latest || ! $user) {
            $this->dispatch('notify', type: 'error', message: 'Thread details are unavailable for reply-all.');

            return;
        }

        $userEmail = Str::lower(trim((string) $user->email));
        $toRecipients = collect(array_merge(
            [trim((string) $latest->from_email)],
            (array) $latest->to_emails
        ))
            ->map(fn ($email) => Str::lower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && $email !== $userEmail)
            ->unique()
            ->values();

        $ccRecipients = collect((array) $latest->cc_emails)
            ->map(fn ($email) => Str::lower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && $email !== $userEmail && ! $toRecipients->contains($email))
            ->unique()
            ->values();

        if ($toRecipients->isEmpty()) {
            $counterpart = trim((string) ($snapshot['counterpart_email'] ?? ''));
            if ($counterpart !== '' && Str::lower($counterpart) !== $userEmail) {
                $toRecipients = collect([$counterpart]);
            }
        }

        if ($toRecipients->isEmpty()) {
            $this->dispatch('notify', type: 'error', message: 'No valid recipients found for reply-all.');

            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'Reply');
        if (! str_starts_with(Str::lower($subject), 're:')) {
            $subject = 'Re: '.$subject;
        }

        $this->showComposer = true;
        $this->composeTo = $toRecipients->implode(', ');
        $this->composeCc = $ccRecipients->implode(', ');
        $this->composeBcc = '';
        $this->composeSubject = $subject;
        $this->composeBody = $this->replyDraft !== '' ? $this->replyDraft : '';
    }

    public function openComposerForForward(): void
    {
        $this->clearComposeStatus();
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $latest = $this->selectedLatestMessageModel();
        if (! $latest) {
            $this->dispatch('notify', type: 'error', message: 'Thread details are unavailable for forwarding.');

            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'Forward');
        if (! str_starts_with(Str::lower($subject), 'fwd:') && ! str_starts_with(Str::lower($subject), 'fw:')) {
            $subject = 'Fwd: '.$subject;
        }

        $quotedBlock = implode("\n", array_filter([
            '',
            '---------- Forwarded message ----------',
            'From: '.trim((string) ($latest->from_name ?: $latest->from_email)),
            'Date: '.$this->formatMessageDateTime($latest->sent_at),
            'Subject: '.((string) ($latest->subject ?: '(No subject)')),
            'To: '.implode(', ', (array) $latest->to_emails),
            ! empty($latest->cc_emails) ? 'Cc: '.implode(', ', (array) $latest->cc_emails) : null,
            '',
            trim((string) ($latest->snippet ?: '')),
        ]));

        $this->showComposer = true;
        $this->composeTo = '';
        $this->composeCc = '';
        $this->composeBcc = '';
        $this->composeSubject = $subject;
        $this->composeBody = trim($this->replyDraft) !== '' ? trim($this->replyDraft)."\n\n".$quotedBlock : $quotedBlock;
    }

    public function closeComposer(): void
    {
        $this->clearComposeStatus();
        $this->showComposer = false;
        $this->composeBody = '';
        $this->composeSubject = '';
        $this->composeTo = '';
        $this->composeCc = '';
        $this->composeBcc = '';
    }

    public function syncGmail(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($this->isSyncingGmail) {
            return;
        }

        $this->isSyncingGmail = true;

        try {
            $gmailService = app(GoogleGmailService::class);

            if (! $gmailService->isConnected($user)) {
                $this->dispatch('notify', type: 'warning', message: 'Google is not connected. Connect Google first.');
                $this->recordInboxAction(
                    suggestionKey: 'sync_gmail',
                    actionLabel: 'Sync Gmail',
                    actionStatus: 'failed',
                    snapshot: null,
                    details: ['reason' => 'google_not_connected']
                );
            } else {
                // Perform a fast inline sync first so the Inbox updates immediately for the user.
                $inlineSummary = $gmailService->syncRecentMessages($user, 30, 120);

                $queueName = (string) (config('queue.gmail_queue') ?: env('GMAIL_SYNC_QUEUE', 'default'));
                try {
                    // Keep deeper backfill queued so history continues to catch up when workers are healthy.
                    SyncGmailMessages::dispatch($user, 90, 300)->onQueue($queueName);
                } catch (\Throwable $queueException) {
                    \Log::warning('Failed to enqueue background Gmail sync after inline sync', [
                        'user_id' => $user->id,
                        'queue' => $queueName,
                        'error' => $queueException->getMessage(),
                    ]);
                }

                $imported = (int) ($inlineSummary['imported'] ?? 0);
                $updated = (int) ($inlineSummary['updated'] ?? 0);
                $processed = (int) ($inlineSummary['processed'] ?? 0);
                $errors = (int) ($inlineSummary['errors'] ?? 0);

                $this->dispatch(
                    'notify',
                    type: 'success',
                    message: "Gmail sync complete (processed {$processed}, imported {$imported}, updated {$updated}, errors {$errors})."
                );
                $this->recordInboxAction(
                    suggestionKey: 'sync_gmail',
                    actionLabel: 'Sync Gmail',
                    actionStatus: 'applied',
                    snapshot: null,
                    details: [
                        'queue' => $queueName,
                        'inline_days_back' => 30,
                        'inline_max_messages' => 120,
                        'queued_days_back' => 90,
                        'queued_max_messages' => 300,
                        'inline_summary' => $inlineSummary,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: 'Gmail sync failed: '.$exception->getMessage());
            $this->recordInboxAction(
                suggestionKey: 'sync_gmail',
                actionLabel: 'Sync Gmail',
                actionStatus: 'failed',
                snapshot: null,
                details: ['error' => $exception->getMessage()]
            );
        } finally {
            $user->refresh();
            $this->lastGmailSyncAt = $user->gmail_import_date?->diffForHumans();
            $this->isSyncingGmail = false;
        }
    }

    public function sendCompose(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->clearComposeStatus();

        $to = trim($this->composeTo);
        $subject = trim($this->composeSubject);
        $body = trim($this->composeBody);

        if ($to === '' || $subject === '' || $body === '') {
            $this->setComposeStatus('error', 'To, subject, and message are required.');
            $this->dispatch('notify', type: 'error', message: 'To, subject, and message are required.');

            return;
        }

        try {
            $result = app(GoogleGmailService::class)->sendMessage(
                $user,
                $to,
                $subject,
                $body,
                [
                    'cc' => $this->composeCc,
                    'bcc' => $this->composeBcc,
                ]
            );

            $this->recordInboxAction(
                suggestionKey: 'compose_send',
                actionLabel: 'Send composed email',
                actionStatus: 'applied',
                snapshot: null,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => (string) ($result['message_id'] ?? ''),
                    'thread_id' => (string) ($result['thread_id'] ?? ''),
                ]
            );

            $this->setComposeStatus('success', 'Email sent.');
            $this->dispatch('notify', type: 'success', message: 'Email sent.');
            $this->showComposer = false;
            $this->composeBody = '';
            $this->composeSubject = '';
            $this->composeTo = '';
            $this->composeCc = '';
            $this->composeBcc = '';
            $this->syncGmail();
        } catch (\Throwable $exception) {
            $this->recordInboxAction(
                suggestionKey: 'compose_send',
                actionLabel: 'Send composed email',
                actionStatus: 'failed',
                snapshot: null,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $exception->getMessage(),
                ]
            );

            $this->setComposeStatus('error', 'Send failed: '.$exception->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Send failed: '.$exception->getMessage());
        }
    }

    public function saveComposeDraft(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->clearComposeStatus();

        $to = trim($this->composeTo);
        $subject = trim($this->composeSubject);
        $body = trim($this->composeBody);

        if ($to === '' || $subject === '' || $body === '') {
            $this->setComposeStatus('error', 'To, subject, and message are required to save a Gmail draft.');
            $this->dispatch('notify', type: 'error', message: 'To, subject, and message are required to save a Gmail draft.');

            return;
        }

        try {
            $result = app(GoogleGmailService::class)->createDraft(
                $user,
                $to,
                $subject,
                $body,
                [
                    'cc' => $this->composeCc,
                    'bcc' => $this->composeBcc,
                ]
            );

            $this->recordInboxAction(
                suggestionKey: 'compose_save_draft',
                actionLabel: 'Save Gmail draft',
                actionStatus: 'applied',
                snapshot: null,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'draft_id' => (string) ($result['draft_id'] ?? ''),
                    'message_id' => (string) ($result['message_id'] ?? ''),
                    'thread_id' => (string) ($result['thread_id'] ?? ''),
                ]
            );

            $this->setComposeStatus('success', 'Draft saved to Gmail.');
            $this->dispatch('notify', type: 'success', message: 'Draft saved to Gmail.');
            $this->syncGmail();
        } catch (\Throwable $exception) {
            $this->recordInboxAction(
                suggestionKey: 'compose_save_draft',
                actionLabel: 'Save Gmail draft',
                actionStatus: 'failed',
                snapshot: null,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $exception->getMessage(),
                ]
            );

            $this->setComposeStatus('error', 'Draft save failed: '.$exception->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Draft save failed: '.$exception->getMessage());
        }
    }

    public function sendReplyDraft(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $to = trim((string) ($snapshot['counterpart_email'] ?? ''));
        $body = trim($this->replyDraft);
        if ($to === '' || $body === '') {
            $this->dispatch('notify', type: 'error', message: 'Reply draft is missing recipient or message.');

            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'Reply');
        if (! str_starts_with(Str::lower($subject), 're:')) {
            $subject = 'Re: '.$subject;
        }

        $threadId = trim((string) ($snapshot['gmail_thread_id'] ?? ''));

        try {
            $result = app(GoogleGmailService::class)->sendMessage(
                $user,
                $to,
                $subject,
                $body,
                ['thread_id' => $threadId]
            );

            $this->recordInboxAction(
                suggestionKey: 'send_reply',
                actionLabel: 'Send reply from thread',
                actionStatus: 'applied',
                snapshot: $snapshot,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => (string) ($result['message_id'] ?? ''),
                    'thread_id' => (string) ($result['thread_id'] ?? $threadId),
                ]
            );

            $this->dispatch('notify', type: 'success', message: 'Reply sent.');
            $this->replyDraft = '';
            $this->syncGmail();
        } catch (\Throwable $exception) {
            $this->recordInboxAction(
                suggestionKey: 'send_reply',
                actionLabel: 'Send reply from thread',
                actionStatus: 'failed',
                snapshot: $snapshot,
                details: [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $exception->getMessage(),
                ]
            );

            $this->dispatch('notify', type: 'error', message: 'Reply send failed: '.$exception->getMessage());
        }
    }

    public function deleteSelectedThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $threadId = trim((string) ($snapshot['gmail_thread_id'] ?? ''));
        if ($threadId === '') {
            $this->dispatch('notify', type: 'error', message: 'No Gmail thread id found for this conversation.');

            return;
        }

        try {
            app(GoogleGmailService::class)->trashThread($user, $threadId);
            $this->applyThreadLabelMutation($threadId, ['TRASH'], ['INBOX', 'SPAM']);
            $this->recordInboxAction(
                suggestionKey: 'delete_thread',
                actionLabel: 'Delete thread',
                actionStatus: 'applied',
                snapshot: $snapshot,
                details: ['gmail_thread_id' => $threadId]
            );
            $this->dispatch('notify', type: 'success', message: 'Thread moved to trash.');
        } catch (\Throwable $exception) {
            $this->recordInboxAction(
                suggestionKey: 'delete_thread',
                actionLabel: 'Delete thread',
                actionStatus: 'failed',
                snapshot: $snapshot,
                details: ['gmail_thread_id' => $threadId, 'error' => $exception->getMessage()]
            );
            $this->dispatch('notify', type: 'error', message: 'Delete failed: '.$exception->getMessage());
        }
    }

    public function markSelectedThreadAsSpam(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $threadId = trim((string) ($snapshot['gmail_thread_id'] ?? ''));
        if ($threadId === '') {
            $this->dispatch('notify', type: 'error', message: 'No Gmail thread id found for this conversation.');

            return;
        }

        try {
            app(GoogleGmailService::class)->markThreadAsSpam($user, $threadId);
            $this->applyThreadLabelMutation($threadId, ['SPAM'], ['INBOX', 'UNREAD']);
            $this->recordInboxAction(
                suggestionKey: 'mark_spam',
                actionLabel: 'Mark thread as spam',
                actionStatus: 'applied',
                snapshot: $snapshot,
                details: ['gmail_thread_id' => $threadId]
            );
            $this->dispatch('notify', type: 'success', message: 'Thread marked as spam.');
        } catch (\Throwable $exception) {
            $this->recordInboxAction(
                suggestionKey: 'mark_spam',
                actionLabel: 'Mark thread as spam',
                actionStatus: 'failed',
                snapshot: $snapshot,
                details: ['gmail_thread_id' => $threadId, 'error' => $exception->getMessage()]
            );
            $this->dispatch('notify', type: 'error', message: 'Spam action failed: '.$exception->getMessage());
        }
    }

    public function saveThreadToProject(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a project first.');

            return;
        }

        $project = Project::query()->find($projectId);
        if (! $project) {
            $this->dispatch('notify', type: 'error', message: 'Selected project was not found.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $subject = trim((string) ($snapshot['subject'] ?? 'Email thread'));
        $counterpart = trim((string) ($snapshot['counterpart_name'] ?? 'Unknown sender'));
        $counterpartEmail = trim((string) ($snapshot['counterpart_email'] ?? ''));
        $snippet = trim((string) ($snapshot['latest_snippet'] ?? ''));
        $sentAt = $snapshot['latest_sent_at'] instanceof Carbon ? $snapshot['latest_sent_at']->format('M j, Y g:i A') : 'Unknown';

        $content = implode("\n", array_filter([
            'Saved from Inbox thread.',
            'Subject: '.$subject,
            'Counterpart: '.$counterpart.($counterpartEmail !== '' ? ' <'.$counterpartEmail.'>' : ''),
            'Latest message: '.$sentAt,
            $snippet !== '' ? 'Snippet: '.$snippet : null,
        ]));

        $project->notes()->create([
            'user_id' => $user->id,
            'content' => $content,
            'note_type' => 'update',
        ]);

        $person = $snapshot['person'] ?? null;
        if ($person instanceof Person) {
            $project->people()->syncWithoutDetaching([
                $person->id => [
                    'role' => 'email counterpart',
                    'notes' => 'Linked from Inbox save-to-project action.',
                ],
            ]);
        }

        $organization = $snapshot['organization'] ?? null;
        if ($organization instanceof Organization) {
            $project->organizations()->syncWithoutDetaching([
                $organization->id => [
                    'role' => 'external counterpart',
                    'notes' => 'Linked from Inbox save-to-project action.',
                ],
            ]);
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_PROJECT,
            $project->id,
            ['source' => 'save_to_project']
        );

        $this->recordInboxAction(
            suggestionKey: 'save_to_project',
            actionLabel: 'Save thread to project',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['project_id' => $project->id, 'project_name' => $project->name]
        );

        $this->dispatch('notify', type: 'success', message: 'Email context saved to project.');
    }

    public function linkThreadToSelectedContact(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $personId = (int) $this->selectedContextPersonId;
        if ($personId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a contact first.');

            return;
        }

        $person = Person::query()->find($personId);
        if (! $person) {
            $this->dispatch('notify', type: 'error', message: 'Selected contact was not found.');

            return;
        }

        $query = $this->threadMessagesQuery($snapshot);
        $query->update(['person_id' => $person->id]);

        $organizationId = (int) $this->selectedContextOrganizationId;
        if ($organizationId > 0 && ! $person->organization_id) {
            $person->organization_id = $organizationId;
            $person->save();
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_PERSON,
            $person->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_contact',
            actionLabel: 'Link thread to contact',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['person_id' => $person->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to contact.');
    }

    public function createContactFromThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $counterpartName = trim((string) ($snapshot['counterpart_name'] ?? ''));
        $counterpartEmail = Str::lower(trim((string) ($snapshot['counterpart_email'] ?? '')));
        $organizationId = (int) $this->selectedContextOrganizationId;

        $person = null;
        if ($counterpartEmail !== '') {
            $person = Person::query()
                ->whereRaw('LOWER(email) = ?', [$counterpartEmail])
                ->first();
        }

        if (! $person) {
            $person = Person::query()->create([
                'name' => $counterpartName !== '' ? $counterpartName : Str::before($counterpartEmail, '@'),
                'email' => $counterpartEmail !== '' ? $counterpartEmail : null,
                'organization_id' => $organizationId > 0 ? $organizationId : null,
            ]);
        } elseif ($organizationId > 0 && ! $person->organization_id) {
            $person->organization_id = $organizationId;
            $person->save();
        }

        $this->selectedContextPersonId = (string) $person->id;
        $this->threadMessagesQuery($snapshot)->update(['person_id' => $person->id]);

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_PERSON,
            $person->id,
            ['source' => 'created_from_thread']
        );

        $this->recordInboxAction(
            suggestionKey: 'create_contact_from_thread',
            actionLabel: 'Create contact from thread',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['person_id' => $person->id, 'email' => $person->email]
        );

        $this->dispatch('notify', type: 'success', message: 'Contact created and linked.');
    }

    public function linkThreadToSelectedOrganization(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $organizationId = (int) $this->selectedContextOrganizationId;
        if ($organizationId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose an organization first.');

            return;
        }

        $organization = Organization::query()->find($organizationId);
        if (! $organization) {
            $this->dispatch('notify', type: 'error', message: 'Selected organization was not found.');

            return;
        }

        $person = $this->selectedLatestMessageModel()?->person;
        if ($person && ! $person->organization_id) {
            $person->organization_id = $organization->id;
            $person->save();
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_ORGANIZATION,
            $organization->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_organization',
            actionLabel: 'Link thread to organization',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['organization_id' => $organization->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to organization.');
    }

    public function createOrganizationFromThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $counterpartEmail = Str::lower(trim((string) ($snapshot['counterpart_email'] ?? '')));
        $counterpartName = trim((string) ($snapshot['counterpart_name'] ?? ''));
        $domain = $counterpartEmail !== '' ? trim((string) Str::after($counterpartEmail, '@')) : '';
        $name = $counterpartName !== '' ? $counterpartName : 'Inbox Organization';
        if ($domain !== '' && Str::contains($domain, '.')) {
            $name = Str::headline((string) Str::before($domain, '.'));
        }

        $organization = Organization::query()->firstOrCreate(
            ['name' => $name],
            [
                'website' => $domain !== '' ? 'https://'.$domain : null,
            ]
        );

        $this->selectedContextOrganizationId = (string) $organization->id;

        $person = $this->selectedLatestMessageModel()?->person;
        if ($person && ! $person->organization_id) {
            $person->organization_id = $organization->id;
            $person->save();
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_ORGANIZATION,
            $organization->id,
            ['source' => 'created_from_thread']
        );

        $this->recordInboxAction(
            suggestionKey: 'create_org_from_thread',
            actionLabel: 'Create organization from thread',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['organization_id' => $organization->id, 'organization_name' => $organization->name]
        );

        $this->dispatch('notify', type: 'success', message: 'Organization created and linked.');
    }

    public function linkThreadToSelectedMeeting(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $meetingId = (int) $this->selectedContextMeetingId;
        if ($meetingId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a meeting first.');

            return;
        }

        $meeting = Meeting::query()->find($meetingId);
        if (! $meeting) {
            $this->dispatch('notify', type: 'error', message: 'Selected meeting was not found.');

            return;
        }

        $person = $this->selectedLatestMessageModel()?->person;
        if ($person instanceof Person) {
            $meeting->people()->syncWithoutDetaching([$person->id]);
        }

        $organization = $person?->organization;
        if ($organization instanceof Organization) {
            $meeting->organizations()->syncWithoutDetaching([$organization->id]);
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId > 0) {
            $meeting->projects()->syncWithoutDetaching([
                $projectId => ['relevance_note' => 'Linked from Inbox thread'],
            ]);
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_MEETING,
            $meeting->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_meeting',
            actionLabel: 'Link thread to meeting',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['meeting_id' => $meeting->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to meeting.');
    }

    public function createMeetingFromThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $meetingDate = $snapshot['latest_sent_at'] instanceof Carbon
            ? $snapshot['latest_sent_at']->toDateString()
            : now()->toDateString();

        $meeting = Meeting::query()->create([
            'user_id' => $user->id,
            'title' => Str::limit((string) ($snapshot['subject'] ?? 'Inbox follow-up'), 180),
            'meeting_date' => $meetingDate,
            'status' => Meeting::STATUS_PENDING,
            'prep_notes' => 'Created from Inbox thread with '.trim((string) ($snapshot['counterpart_name'] ?? 'counterpart')).'.',
        ]);

        $person = $this->selectedLatestMessageModel()?->person;
        if ($person instanceof Person) {
            $meeting->people()->syncWithoutDetaching([$person->id]);
            if ($person->organization_id) {
                $meeting->organizations()->syncWithoutDetaching([$person->organization_id]);
            }
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId > 0) {
            $meeting->projects()->syncWithoutDetaching([
                $projectId => ['relevance_note' => 'Created from Inbox thread'],
            ]);
        }

        $this->selectedContextMeetingId = (string) $meeting->id;

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_MEETING,
            $meeting->id,
            ['source' => 'created_from_thread']
        );

        $this->recordInboxAction(
            suggestionKey: 'create_meeting_from_thread',
            actionLabel: 'Create meeting from thread',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['meeting_id' => $meeting->id, 'meeting_date' => $meetingDate]
        );

        $this->dispatch('notify', type: 'success', message: 'Meeting created and linked.');
    }

    public function linkThreadToSelectedGrant(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->isManagement()) {
            $this->dispatch('notify', type: 'error', message: 'Funding context is limited to management/admin users.');

            return;
        }

        $grantId = (int) $this->selectedGrantId;
        if ($grantId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a grant first.');

            return;
        }

        $grant = Grant::query()
            ->visibleTo($user)
            ->whereKey($grantId)
            ->first();

        if (! $grant) {
            $this->dispatch('notify', type: 'error', message: 'Selected grant was not found or is not visible.');

            return;
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_GRANT,
            $grant->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_grant',
            actionLabel: 'Link thread to grant',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['grant_id' => $grant->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to funder/grant context.');
    }

    public function linkThreadToSelectedMediaContact(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $personId = (int) $this->selectedContextMediaContactId;
        if ($personId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a media contact first.');

            return;
        }

        $person = Person::query()->find($personId);
        if (! $person) {
            $this->dispatch('notify', type: 'error', message: 'Selected media contact was not found.');

            return;
        }

        if (! $person->is_journalist) {
            $person->is_journalist = true;
            $person->save();
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_MEDIA_CONTACT,
            $person->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_media_contact',
            actionLabel: 'Link thread to media contact',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['person_id' => $person->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to media contact.');
    }

    public function linkThreadToSelectedMediaOutlet(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $organizationId = (int) $this->selectedContextMediaOutletId;
        if ($organizationId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a media outlet first.');

            return;
        }

        $organization = Organization::query()->find($organizationId);
        if (! $organization) {
            $this->dispatch('notify', type: 'error', message: 'Selected media outlet was not found.');

            return;
        }

        if (! $organization->type) {
            $organization->type = 'Media';
            $organization->save();
        }

        $this->saveContextLink(
            $snapshot,
            InboxThreadContextLink::TYPE_MEDIA_OUTLET,
            $organization->id,
            ['source' => 'manual_link']
        );

        $this->recordInboxAction(
            suggestionKey: 'link_thread_media_outlet',
            actionLabel: 'Link thread to media outlet',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: ['organization_id' => $organization->id]
        );

        $this->dispatch('notify', type: 'success', message: 'Thread linked to media outlet.');
    }

    public function removeThreadContextLink(int $linkId): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! Schema::hasTable('inbox_thread_context_links')) {
            return;
        }

        $link = InboxThreadContextLink::query()
            ->where('user_id', $user->id)
            ->where('thread_key', (string) ($snapshot['thread_key'] ?? ''))
            ->whereKey($linkId)
            ->first();

        if (! $link) {
            return;
        }

        $link->delete();
        $this->dispatch('notify', type: 'success', message: 'Context link removed.');
    }

    public function createFollowUpTask(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $counterpart = (string) ($snapshot['counterpart_name'] ?? 'contact');
        $subject = (string) ($snapshot['subject'] ?? 'Email follow-up');

        $dueDate = now()->addDay();
        if (($snapshot['sentiment'] ?? 'neutral') === 'critical') {
            $dueDate = now();
        }

        $descriptionParts = [
            'Generated from Inbox thread.',
            'Subject: '.$subject,
        ];

        if (! empty($snapshot['counterpart_email'])) {
            $descriptionParts[] = 'Counterpart: '.$counterpart.' <'.$snapshot['counterpart_email'].'>';
        } else {
            $descriptionParts[] = 'Counterpart: '.$counterpart;
        }

        if (! empty($snapshot['latest_snippet'])) {
            $descriptionParts[] = 'Context: '.$snapshot['latest_snippet'];
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId > 0) {
            $projectName = Project::query()->whereKey($projectId)->value('name');
            if ($projectName) {
                $descriptionParts[] = 'Linked project: '.$projectName;
            }
        }

        $task = Action::createResilient([
            'title' => Str::limit('Follow up with '.$counterpart.' re: '.$subject, 240),
            'description' => implode("\n", $descriptionParts),
            'due_date' => $dueDate->toDateString(),
            'priority' => ($snapshot['sentiment'] ?? 'neutral') === 'critical' ? Action::PRIORITY_HIGH : Action::PRIORITY_MEDIUM,
            'status' => Action::STATUS_PENDING,
            'source' => Action::SOURCE_AI_SUGGESTED,
            'assigned_to' => $user->id,
            'project_id' => $projectId > 0 ? $projectId : null,
        ]);

        $person = $snapshot['person'] ?? null;
        if ($person instanceof Person) {
            PersonInteraction::query()->create([
                'person_id' => $person->id,
                'user_id' => $user->id,
                'type' => 'email',
                'occurred_at' => now(),
                'summary' => 'Follow-up task created from Inbox thread: '.Str::limit($subject, 140),
                'next_action_at' => $dueDate,
                'next_action_note' => 'Reply to '.$counterpart,
            ]);
        }

        $this->recordInboxAction(
            suggestionKey: 'create_follow_up_task',
            actionLabel: 'Create follow-up task',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'created_action_id' => $task->id,
                'due_date' => $dueDate->toDateString(),
                'priority' => $task->priority,
                'project_id' => $projectId > 0 ? $projectId : null,
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Follow-up task created.');
    }

    public function linkPersonToProject(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a project first.');

            return;
        }

        $person = $snapshot['person'] ?? null;
        if (! $person instanceof Person) {
            $this->dispatch('notify', type: 'error', message: 'No contact is linked to this thread yet.');

            return;
        }

        $project = Project::query()->find($projectId);
        if (! $project) {
            $this->dispatch('notify', type: 'error', message: 'Selected project was not found.');

            return;
        }

        $project->people()->syncWithoutDetaching([
            $person->id => [
                'role' => 'email counterpart',
                'notes' => 'Linked from Inbox thread: '.Str::limit((string) ($snapshot['subject'] ?? ''), 180),
            ],
        ]);

        $this->recordInboxAction(
            suggestionKey: 'link_person_project',
            actionLabel: 'Link contact to project',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'person_id' => $person->id,
                'project_id' => $project->id,
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Contact linked to project.');
    }

    public function linkSelectedProjectToGrant(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->isManagement()) {
            $this->dispatch('notify', type: 'error', message: 'Funding links are available to management/admin users only.');

            return;
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a project first.');

            return;
        }

        $grantId = (int) $this->selectedGrantId;
        if ($grantId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a grant first.');

            return;
        }

        $project = Project::query()->find($projectId);
        if (! $project) {
            $this->dispatch('notify', type: 'error', message: 'Selected project was not found.');

            return;
        }

        $grant = Grant::query()
            ->visibleTo($user)
            ->whereKey($grantId)
            ->first();

        if (! $grant) {
            $this->dispatch('notify', type: 'error', message: 'Selected grant was not found or is not visible to you.');

            return;
        }

        $grant->projects()->syncWithoutDetaching([
            $project->id => [
                'notes' => 'Linked from Inbox thread: '.Str::limit((string) ($snapshot['subject'] ?? ''), 180),
            ],
        ]);

        $this->recordInboxAction(
            suggestionKey: 'link_project_grant',
            actionLabel: 'Link project to grant',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'project_id' => $project->id,
                'grant_id' => $grant->id,
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Project linked to grant.');
    }

    public function openMatchedFunder(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $organization = $snapshot['organization'] ?? null;
        if (! $organization || empty($organization->id)) {
            $this->dispatch('notify', type: 'error', message: 'No funder organization found for this thread.');

            return;
        }

        $this->recordInboxAction(
            suggestionKey: 'open_funder_record',
            actionLabel: 'Open funder record',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'organization_id' => (int) $organization->id,
            ]
        );

        $this->redirectRoute('organizations.show', ['organization' => (int) $organization->id], navigate: true);
    }

    public function openSelectedGrantRecord(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Grant records are currently admin-only.');

            return;
        }

        $grantId = (int) $this->selectedGrantId;
        if ($grantId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a grant first.');

            return;
        }

        $grant = Grant::query()->whereKey($grantId)->first();
        if (! $grant) {
            $this->dispatch('notify', type: 'error', message: 'Selected grant was not found.');

            return;
        }

        $this->recordInboxAction(
            suggestionKey: 'open_grant_record',
            actionLabel: 'Open grant record',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'grant_id' => $grant->id,
            ]
        );

        $this->redirectRoute('grants.show', ['grant' => $grant->id], navigate: true);
    }

    public function createProjectFromThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'New initiative');
        $counterpart = (string) ($snapshot['counterpart_name'] ?? 'email thread');

        $name = $this->deriveProjectNameFromSubject($subject);

        $project = Project::query()->create([
            'name' => $name,
            'status' => 'planning',
            'project_type' => 'initiative',
            'created_by' => $user->id,
            'description' => 'Created from Inbox thread with '.$counterpart.'.',
            'tags' => ['inbox-origin'],
        ]);

        $person = $snapshot['person'] ?? null;
        if ($person instanceof Person) {
            $project->people()->syncWithoutDetaching([
                $person->id => [
                    'role' => 'contact',
                    'notes' => 'Added at project creation from Inbox thread.',
                ],
            ]);
        }

        $this->selectedProjectId = (string) $project->id;

        $this->recordInboxAction(
            suggestionKey: 'create_project',
            actionLabel: 'Create project from thread',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'created_project_id' => $project->id,
                'project_name' => $project->name,
                'person_id' => $person?->id,
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Project created and linked.');
    }

    public function generateReplyDraft(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $name = (string) ($snapshot['counterpart_name'] ?? 'there');
        $subject = (string) ($snapshot['subject'] ?? 'your note');
        $sentiment = (string) ($snapshot['sentiment'] ?? 'neutral');

        $opening = match ($sentiment) {
            'critical' => 'Thank you for flagging this. I appreciate the detailed context and want to address it carefully.',
            'positive' => 'Thanks for this update. Great to hear your momentum on this.',
            default => 'Thanks for your note. I received this and am following up now.',
        };

        $this->replyDraft = "Hi {$name},\n\n"
            .$opening."\n\n"
            .'I reviewed your email regarding "'.$subject.'" and will follow up with concrete next steps shortly.'
            ."\n\nBest,\n{$user->name}";

        $this->recordInboxAction(
            suggestionKey: 'generate_reply_draft',
            actionLabel: 'Generate reply draft',
            actionStatus: 'applied',
            snapshot: $snapshot,
            details: [
                'draft_length' => Str::length($this->replyDraft),
                'sentiment' => $sentiment,
            ]
        );
    }

    public function clearReplyDraft(): void
    {
        $this->replyDraft = '';
    }

    public function getFolderCountsProperty(): array
    {
        $messages = $this->baseMessages();

        return [
            'inbox' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'inbox'))->count(),
            'sent' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'sent'))->count(),
            'drafts' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'drafts'))->count(),
            'archive' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'archive'))->count(),
            'all' => $messages->count(),
        ];
    }

    public function getThreadSummariesProperty(): array
    {
        return $this->groupedMessages()
            ->map(function (Collection $messages, string $threadKey) {
                $latest = $messages
                    ->sortByDesc(fn (GmailMessage $message) => $message->sent_at?->timestamp ?? 0)
                    ->first();

                if (! $latest) {
                    return null;
                }

                $counterpart = $this->counterpartForMessage($latest);
                $sentiment = $this->threadSentiment($messages);

                return [
                    'thread_key' => $threadKey,
                    'subject' => $latest->subject ?: '(No subject)',
                    'preview' => $latest->snippet ?: 'No preview available.',
                    'date_label' => $this->formatThreadDate($latest->sent_at),
                    'counterpart_name' => $counterpart['name'],
                    'counterpart_email' => $counterpart['email'],
                    'message_count' => $messages->count(),
                    'labels' => $this->visibleLabels($latest->labels ?? []),
                    'sentiment' => $sentiment,
                    'sort_at' => $latest->sent_at?->timestamp ?? 0,
                ];
            })
            ->filter()
            ->sortByDesc('sort_at')
            ->values()
            ->all();
    }

    public function getSelectedThreadProperty(): ?array
    {
        return $this->selectedThreadSnapshot();
    }

    public function getInboxActionLogsProperty(): Collection
    {
        $user = Auth::user();
        if (! $user || ! Schema::hasTable('inbox_action_logs')) {
            return collect();
        }

        return InboxActionLog::query()
            ->where('user_id', $user->id)
            ->with(['project'])
            ->latest()
            ->limit(24)
            ->get();
    }

    protected function recordInboxAction(
        string $suggestionKey,
        string $actionLabel,
        string $actionStatus,
        ?array $snapshot = null,
        array $details = []
    ): void {
        $user = Auth::user();
        if (! $user || ! Schema::hasTable('inbox_action_logs')) {
            return;
        }

        $threadKey = null;
        $subject = null;
        $counterpartName = null;
        $counterpartEmail = null;
        $gmailMessageId = null;
        $projectId = null;

        if (is_array($snapshot)) {
            $threadKey = (string) ($snapshot['thread_key'] ?? '');
            $subject = (string) ($snapshot['subject'] ?? '');
            $counterpartName = (string) ($snapshot['counterpart_name'] ?? '');
            $counterpartEmail = (string) ($snapshot['counterpart_email'] ?? '');
            $projectId = (int) ($this->selectedProjectId !== '' ? $this->selectedProjectId : 0);

            $latestMessage = collect($snapshot['messages'] ?? [])->last();
            $gmailMessageId = (int) ($latestMessage['id'] ?? 0);
        }

        try {
            InboxActionLog::query()->create([
                'user_id' => $user->id,
                'gmail_message_id' => $gmailMessageId > 0 ? $gmailMessageId : null,
                'project_id' => $projectId > 0 ? $projectId : null,
                'thread_key' => $threadKey !== '' ? $threadKey : null,
                'suggestion_key' => $suggestionKey,
                'action_label' => $actionLabel,
                'action_status' => $actionStatus,
                'subject' => $subject !== '' ? Str::limit($subject, 255, '') : null,
                'counterpart_name' => $counterpartName !== '' ? Str::limit($counterpartName, 255, '') : null,
                'counterpart_email' => $counterpartEmail !== '' ? Str::limit($counterpartEmail, 255, '') : null,
                'details' => $details,
            ]);
        } catch (\Throwable $exception) {
            \Log::warning('Inbox action log write failed', [
                'user_id' => $user->id,
                'suggestion_key' => $suggestionKey,
                'action_label' => $actionLabel,
                'action_status' => $actionStatus,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function clearComposeStatus(): void
    {
        $this->composeStatus = '';
        $this->composeStatusType = 'info';
    }

    protected function setComposeStatus(string $type, string $message): void
    {
        $this->composeStatusType = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
        $this->composeStatus = trim($message);
    }

    protected function selectedThreadSnapshot(): ?array
    {
        $groups = $this->groupedMessages();
        if ($groups->isEmpty()) {
            return null;
        }

        if (! $this->selectedThreadKey || ! $groups->has($this->selectedThreadKey)) {
            $this->selectedThreadKey = (string) $groups->keys()->first();
            $this->selectedProjectId = '';
            $this->selectedGrantId = '';
            $this->replyDraft = '';
        }

        $messages = $groups->get($this->selectedThreadKey);
        if (! $messages instanceof Collection || $messages->isEmpty()) {
            return null;
        }

        $messages = $messages
            ->sortBy(fn (GmailMessage $message) => $message->sent_at?->timestamp ?? 0)
            ->values();

        /** @var GmailMessage|null $latest */
        $latest = $messages->last();
        if (! $latest) {
            return null;
        }

        $person = $latest->person;
        $organization = $person?->organization;
        $projectCandidates = $this->projectCandidates($messages, $person)->values();
        $grantCandidates = $this->grantCandidates($messages, $person, $organization)->values();

        if ($projectCandidates->isNotEmpty()) {
            $candidateIds = $projectCandidates->pluck('id')->map(fn ($id) => (string) $id)->all();
            // Respect explicit "No project selected" in the UI; only clear invalid stale values.
            if ($this->selectedProjectId !== '' && ! in_array((string) $this->selectedProjectId, $candidateIds, true)) {
                $this->selectedProjectId = '';
            }
        } else {
            $this->selectedProjectId = '';
        }

        if ($grantCandidates->isNotEmpty()) {
            $candidateIds = $grantCandidates->pluck('id')->map(fn ($id) => (string) $id)->all();
            // Respect explicit "No grant selected" in the UI; only clear invalid stale values.
            if ($this->selectedGrantId !== '' && ! in_array((string) $this->selectedGrantId, $candidateIds, true)) {
                $this->selectedGrantId = '';
            }
        } else {
            $this->selectedGrantId = '';
        }

        $isPersonLinkedToSelectedProject = false;
        if ($person && $this->selectedProjectId !== '') {
            $isPersonLinkedToSelectedProject = $person->projects()
                ->where('projects.id', (int) $this->selectedProjectId)
                ->exists();
        }

        $isProjectLinkedToSelectedGrant = false;
        if ($this->selectedProjectId !== '' && $this->selectedGrantId !== '' && $this->canUseFundingContext()) {
            $isProjectLinkedToSelectedGrant = Grant::query()
                ->visibleTo(Auth::user())
                ->whereKey((int) $this->selectedGrantId)
                ->whereHas('projects', fn ($query) => $query->where('projects.id', (int) $this->selectedProjectId))
                ->exists();
        }

        $counterpart = $this->counterpartForMessage($latest);
        $analysis = $this->agentAnalysis($messages, $latest, $projectCandidates, $grantCandidates, $person, $organization);

        $suggestions = $this->threadSuggestions(
            $messages,
            $latest,
            $projectCandidates,
            $grantCandidates,
            $person,
            $organization,
            $isPersonLinkedToSelectedProject,
            $isProjectLinkedToSelectedGrant
        );

        return [
            'thread_key' => $this->selectedThreadKey,
            'subject' => $latest->subject ?: '(No subject)',
            'messages' => $messages->map(function (GmailMessage $message) {
                $counterpart = $this->counterpartForMessage($message);

                return [
                    'id' => $message->id,
                    'is_inbound' => (bool) $message->is_inbound,
                    'sender_name' => $counterpart['name'],
                    'sender_email' => $counterpart['email'],
                    'snippet' => $message->snippet ?: 'No message snippet available.',
                    'sent_label' => $this->formatMessageDateTime($message->sent_at),
                    'labels' => $this->visibleLabels($message->labels ?? []),
                ];
            })->all(),
            'person' => $person,
            'organization' => $organization,
            'counterpart_name' => $counterpart['name'],
            'counterpart_email' => $counterpart['email'],
            'gmail_thread_id' => (string) ($latest->gmail_thread_id ?? ''),
            'latest_snippet' => $latest->snippet,
            'sentiment' => $this->threadSentiment($messages),
            'project_candidates' => $projectCandidates->all(),
            'grant_candidates' => $grantCandidates->all(),
            'is_person_linked_to_selected_project' => $isPersonLinkedToSelectedProject,
            'is_project_linked_to_selected_grant' => $isProjectLinkedToSelectedGrant,
            'agent_analysis' => $analysis,
            'suggestions' => $suggestions,
            'latest_sent_at' => $latest->sent_at,
        ];
    }

    protected function threadSuggestions(
        Collection $messages,
        GmailMessage $latest,
        Collection $projectCandidates,
        Collection $grantCandidates,
        ?Person $person,
        $organization,
        bool $isPersonLinkedToSelectedProject,
        bool $isProjectLinkedToSelectedGrant
    ): array {
        $suggestions = [
            [
                'key' => 'create_follow_up_task',
                'title' => 'Create follow-up task',
                'body' => 'Capture this thread as a tracked follow-up action in WRK.',
                'action' => 'createFollowUpTask',
                'button' => 'Create task',
            ],
            [
                'key' => 'generate_reply_draft',
                'title' => 'Draft reply',
                'body' => 'Generate a first-pass response, edit it, then send from this inbox.',
                'action' => 'generateReplyDraft',
                'button' => 'Generate draft',
            ],
        ];

        if ($person && $this->selectedProjectId !== '' && ! $isPersonLinkedToSelectedProject) {
            $suggestions[] = [
                'key' => 'link_person_project',
                'title' => 'Link contact to selected project',
                'body' => $person->name.' is not yet linked to the selected project. Add them as an email counterpart.',
                'action' => 'linkPersonToProject',
                'button' => 'Link contact',
            ];
        }

        $shouldSuggestProject = $projectCandidates->isEmpty() && $this->looksLikeProjectThread($messages, $latest, $organization);
        if ($shouldSuggestProject) {
            $suggestions[] = [
                'key' => 'create_project',
                'title' => 'Create project from thread',
                'body' => 'No clear project match found. Create a planning project and link this thread context.',
                'action' => 'createProjectFromThread',
                'button' => 'Create project',
            ];
        }

        if ($this->canUseFundingContext()) {
            if ($organization && ($organization->is_funder || $grantCandidates->isNotEmpty())) {
                $suggestions[] = [
                    'key' => 'open_funder_record',
                    'title' => 'Open funder record',
                    'body' => ($organization->name ?? 'This organization').' appears funder-linked. Open the organization record for context.',
                    'action' => 'openMatchedFunder',
                    'button' => 'Open funder',
                ];
            }

            if ($grantCandidates->isNotEmpty() && $this->selectedProjectId !== '' && ! $isProjectLinkedToSelectedGrant) {
                $grantName = (string) ($grantCandidates->first()['name'] ?? 'selected grant');
                $suggestions[] = [
                    'key' => 'link_project_grant',
                    'title' => 'Link selected project to grant',
                    'body' => 'Associate this thread workflow with '.$grantName.' for funding visibility.',
                    'action' => 'linkSelectedProjectToGrant',
                    'button' => 'Link grant',
                ];
            }

            $actor = Auth::user();
            if ($grantCandidates->isNotEmpty() && $actor && $actor->isAdmin()) {
                $suggestions[] = [
                    'key' => 'open_grant_record',
                    'title' => 'Open grant record',
                    'body' => 'Review the matched grant details and reporting context.',
                    'action' => 'openSelectedGrantRecord',
                    'button' => 'Open grant',
                ];
            }
        }

        return $suggestions;
    }

    protected function projectCandidates(Collection $messages, ?Person $person): Collection
    {
        $allProjects = Project::query()
            ->whereIn('status', ['planning', 'active', 'on_hold'])
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'status']);

        $messageText = Str::lower(
            $messages
                ->take(-8)
                ->map(fn (GmailMessage $message) => trim((string) ($message->subject.' '.$message->snippet)))
                ->implode(' ')
        );

        $matchedByText = $allProjects
            ->filter(fn (Project $project) => $this->projectMentionedInText($project->name, $messageText))
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'source' => 'thread_match',
            ]);

        $matchedByPerson = collect();
        if ($person) {
            $matchedByPerson = $person->projects()
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->orderBy('projects.name')
                ->limit(12)
                ->get(['projects.id', 'projects.name', 'projects.status'])
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'source' => 'contact_link',
                ]);
        }

        return $matchedByPerson
            ->concat($matchedByText)
            ->unique('id')
            ->values();
    }

    protected function grantCandidates(Collection $messages, ?Person $person, $organization): Collection
    {
        if (! $this->canUseFundingContext()) {
            return collect();
        }

        $actor = Auth::user();
        if (! $actor) {
            return collect();
        }

        $visibleGrants = Grant::query()
            ->visibleTo($actor)
            ->with('funder:id,name')
            ->whereIn('status', ['prospective', 'pending', 'active', 'completed'])
            ->orderByDesc('updated_at')
            ->limit(250)
            ->get(['id', 'organization_id', 'name', 'status', 'visibility']);

        $organizationId = (int) ($organization?->id ?? $person?->organization_id ?? 0);

        $matchedByOrganization = $visibleGrants
            ->filter(fn (Grant $grant) => $organizationId > 0 && (int) $grant->organization_id === $organizationId)
            ->map(fn (Grant $grant) => [
                'id' => $grant->id,
                'name' => $grant->name,
                'status' => $grant->status,
                'visibility' => $grant->visibility,
                'funder_name' => (string) ($grant->funder?->name ?? ''),
                'source' => 'funder_match',
            ]);

        $messageText = Str::lower(
            $messages
                ->take(-8)
                ->map(fn (GmailMessage $message) => trim((string) ($message->subject.' '.$message->snippet)))
                ->implode(' ')
        );

        $matchedByText = $visibleGrants
            ->filter(fn (Grant $grant) => $this->grantMentionedInText($grant->name, $messageText))
            ->map(fn (Grant $grant) => [
                'id' => $grant->id,
                'name' => $grant->name,
                'status' => $grant->status,
                'visibility' => $grant->visibility,
                'funder_name' => (string) ($grant->funder?->name ?? ''),
                'source' => 'thread_match',
            ]);

        $selectedProjectId = (int) $this->selectedProjectId;
        $matchedByProject = collect();
        if ($selectedProjectId > 0) {
            $projectGrantIds = Grant::query()
                ->visibleTo($actor)
                ->whereHas('projects', fn ($query) => $query->where('projects.id', $selectedProjectId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $matchedByProject = $visibleGrants
                ->filter(fn (Grant $grant) => in_array((int) $grant->id, $projectGrantIds, true))
                ->map(fn (Grant $grant) => [
                    'id' => $grant->id,
                    'name' => $grant->name,
                    'status' => $grant->status,
                    'visibility' => $grant->visibility,
                    'funder_name' => (string) ($grant->funder?->name ?? ''),
                    'source' => 'project_link',
                ]);
        }

        // Always provide a broader fallback pool so users can override agent matching.
        $fallbackRecent = $visibleGrants
            ->take(40)
            ->map(fn (Grant $grant) => [
                'id' => $grant->id,
                'name' => $grant->name,
                'status' => $grant->status,
                'visibility' => $grant->visibility,
                'funder_name' => (string) ($grant->funder?->name ?? ''),
                'source' => 'recent',
            ]);

        return $matchedByOrganization
            ->concat($matchedByText)
            ->concat($matchedByProject)
            ->concat($fallbackRecent)
            ->unique('id')
            ->take(40)
            ->values();
    }

    protected function projectMentionedInText(string $projectName, string $messageText): bool
    {
        $name = Str::of(Str::lower($projectName))
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();

        if ($name === '' || mb_strlen($name) < 4) {
            return false;
        }

        if (Str::contains($messageText, $name)) {
            return true;
        }

        $parts = collect(explode(' ', $name))
            ->filter(fn (string $part) => mb_strlen($part) >= 4)
            ->values();

        if ($parts->count() < 2) {
            return false;
        }

        $matches = $parts->filter(fn (string $part) => Str::contains($messageText, $part))->count();

        return $matches >= 2;
    }

    protected function grantMentionedInText(string $grantName, string $messageText): bool
    {
        $name = Str::of(Str::lower($grantName))
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();

        if ($name === '' || mb_strlen($name) < 4) {
            return false;
        }

        if (Str::contains($messageText, $name)) {
            return true;
        }

        $parts = collect(explode(' ', $name))
            ->filter(fn (string $part) => mb_strlen($part) >= 4)
            ->values();

        if ($parts->count() < 2) {
            return false;
        }

        $matches = $parts->filter(fn (string $part) => Str::contains($messageText, $part))->count();

        return $matches >= 2;
    }

    protected function canUseFundingContext(): bool
    {
        $actor = Auth::user();

        return (bool) ($actor && $actor->isManagement());
    }

    protected function looksLikeProjectThread(Collection $messages, GmailMessage $latest, $organization): bool
    {
        $text = Str::lower($messages->take(-8)->map(fn (GmailMessage $message) => ($message->subject ?? '').' '.($message->snippet ?? ''))->implode(' '));

        $keywords = [
            'project', 'initiative', 'proposal', 'pilot', 'phase', 'workplan', 'scope',
            'deliverable', 'timeline', 'kickoff', 'partnership', 'collaboration', 'contract',
        ];

        $hasKeyword = collect($keywords)->contains(fn (string $keyword) => Str::contains($text, $keyword));

        if (! $hasKeyword) {
            return false;
        }

        if ($organization) {
            $existing = Project::query()
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organization->id))
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->count();

            if ($existing > 0) {
                return false;
            }
        }

        return true;
    }

    protected function agentAnalysis(
        Collection $messages,
        GmailMessage $latest,
        Collection $projectCandidates,
        Collection $grantCandidates,
        ?Person $person,
        $organization
    ): string {
        $sentiment = $this->threadSentiment($messages);

        $parts = [];
        if ($sentiment === 'critical') {
            $parts[] = 'This thread has urgency/conflict signals and should be handled quickly.';
        } elseif ($sentiment === 'positive') {
            $parts[] = 'This thread appears constructive and may be a momentum opportunity.';
        } else {
            $parts[] = 'This thread looks informational and suitable for standard follow-up.';
        }

        if ($projectCandidates->isNotEmpty()) {
            $parts[] = 'Likely linked project: '.$projectCandidates->first()['name'].'.';
        } else {
            $parts[] = 'No strong project match found yet.';
        }

        if ($this->canUseFundingContext()) {
            if ($grantCandidates->isNotEmpty()) {
                $grant = $grantCandidates->first();
                $funder = trim((string) ($grant['funder_name'] ?? ''));
                $grantLabel = (string) ($grant['name'] ?? 'matched grant');
                $parts[] = $funder !== ''
                    ? 'Potential grant/funder context: '.$grantLabel.' ('.$funder.').'
                    : 'Potential grant context: '.$grantLabel.'.';
            } else {
                $parts[] = 'No clear grant/funder match found yet.';
            }
        }

        if ($person) {
            $parts[] = $person->name.' is recognized in Contacts.';
        } else {
            $parts[] = 'Counterpart is not yet linked to a contact record.';
        }

        if ($organization) {
            $parts[] = 'Organization context: '.$organization->name.'.';
        }

        if ($latest->is_inbound) {
            $parts[] = 'Recommended next step: create a follow-up task and draft a reply.';
        } else {
            $parts[] = 'Recommended next step: track expected response and due date.';
        }

        return implode(' ', $parts);
    }

    protected function threadSentiment(Collection $messages): string
    {
        $text = Str::lower(
            $messages
                ->take(-8)
                ->map(fn (GmailMessage $message) => ($message->subject ?? '').' '.($message->snippet ?? ''))
                ->implode(' ')
        );

        $criticalWords = ['urgent', 'concern', 'problem', 'issue', 'blocked', 'conflict', 'risk', 'delay'];
        if (collect($criticalWords)->contains(fn (string $word) => Str::contains($text, $word))) {
            return 'critical';
        }

        $positiveWords = ['great', 'excited', 'thanks', 'appreciate', 'love', 'opportunity', 'happy'];
        if (collect($positiveWords)->contains(fn (string $word) => Str::contains($text, $word))) {
            return 'positive';
        }

        return 'neutral';
    }

    protected function counterpartForMessage(GmailMessage $message): array
    {
        if ($message->person) {
            return [
                'name' => $message->person->name,
                'email' => $message->person->email ?: ($message->from_email ?: ''),
            ];
        }

        $name = trim((string) ($message->from_name ?: ''));
        $email = trim((string) ($message->from_email ?: ''));

        if ($name === '' && $email !== '') {
            $name = Str::before($email, '@');
        }

        if ($name === '') {
            $name = 'Unknown sender';
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }

    protected function deriveProjectNameFromSubject(string $subject): string
    {
        $clean = Str::of($subject)
            ->replaceMatches('/^\s*(re|fwd|fw)\s*:\s*/i', '')
            ->replaceMatches('/[^A-Za-z0-9\s\-\/]/', '')
            ->squish()
            ->value();

        if ($clean === '') {
            return 'New Project from Inbox';
        }

        return Str::limit($clean, 100, '');
    }

    protected function groupedMessages(): Collection
    {
        return $this->filteredMessages()->groupBy(fn (GmailMessage $message) => $this->threadKeyForMessage($message));
    }

    protected function filteredMessages(): Collection
    {
        $messages = $this->baseMessages()->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, $this->folder));

        $query = trim($this->search);
        if ($query === '') {
            return $messages->values();
        }

        $needle = Str::lower($query);

        return $messages
            ->filter(function (GmailMessage $message) use ($needle) {
                $haystack = Str::lower(implode(' ', [
                    (string) $message->subject,
                    (string) $message->snippet,
                    (string) $message->from_name,
                    (string) $message->from_email,
                    (string) ($message->person?->name ?? ''),
                    (string) ($message->person?->email ?? ''),
                    (string) ($message->person?->organization?->name ?? ''),
                ]));

                return Str::contains($haystack, $needle);
            })
            ->values();
    }

    protected function baseMessages(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return GmailMessage::query()
            ->where('user_id', $user->id)
            ->with(['person.organization'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit(1200)
            ->get();
    }

    protected function threadKeyForMessage(GmailMessage $message): string
    {
        $threadId = trim((string) $message->gmail_thread_id);
        if ($threadId !== '') {
            return $threadId;
        }

        return 'msg:'.(string) $message->gmail_message_id;
    }

    protected function selectedLatestMessageModel(): ?GmailMessage
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            return null;
        }

        return $this->threadMessagesQuery($snapshot)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function threadMessagesQuery(array $snapshot)
    {
        $user = Auth::user();
        $query = GmailMessage::query();
        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $query->where('user_id', $user->id);

        $threadId = trim((string) ($snapshot['gmail_thread_id'] ?? ''));
        if ($threadId !== '') {
            return $query->where('gmail_thread_id', $threadId);
        }

        $messageIds = collect($snapshot['messages'] ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($messageIds === []) {
            return $query->whereRaw('1=0');
        }

        return $query->whereIn('id', $messageIds);
    }

    protected function applyThreadLabelMutation(string $threadId, array $add = [], array $remove = []): void
    {
        $threadId = trim($threadId);
        $user = Auth::user();
        if ($threadId === '' || ! $user) {
            return;
        }

        $addLabels = collect($add)
            ->map(fn ($label) => Str::upper(trim((string) $label)))
            ->filter()
            ->values()
            ->all();
        $removeLabels = collect($remove)
            ->map(fn ($label) => Str::upper(trim((string) $label)))
            ->filter()
            ->values()
            ->all();

        $messages = GmailMessage::query()
            ->where('user_id', $user->id)
            ->where('gmail_thread_id', $threadId)
            ->get();

        foreach ($messages as $message) {
            $labels = collect($message->labels ?? [])
                ->map(fn ($label) => Str::upper(trim((string) $label)))
                ->filter()
                ->values();

            if ($removeLabels !== []) {
                $labels = $labels->reject(fn ($label) => in_array($label, $removeLabels, true))->values();
            }

            if ($addLabels !== []) {
                $labels = $labels->concat($addLabels)->unique()->values();
            }

            $message->labels = $labels->all();
            $message->save();
        }
    }

    protected function saveContextLink(array $snapshot, string $linkType, int $linkId, array $metadata = []): void
    {
        $user = Auth::user();
        if (! $user || $linkId <= 0 || ! Schema::hasTable('inbox_thread_context_links')) {
            return;
        }

        $threadKey = trim((string) ($snapshot['thread_key'] ?? $this->selectedThreadKey ?? ''));
        if ($threadKey === '') {
            return;
        }

        InboxThreadContextLink::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'thread_key' => $threadKey,
                'link_type' => $linkType,
                'link_id' => $linkId,
            ],
            [
                'gmail_thread_id' => trim((string) ($snapshot['gmail_thread_id'] ?? '')) ?: null,
                'created_by' => $user->id,
                'metadata' => $metadata !== [] ? $metadata : null,
            ]
        );
    }

    protected function messageMatchesFolder(GmailMessage $message, string $folder): bool
    {
        return match ($folder) {
            'inbox' => $this->messageHasLabel($message, 'INBOX'),
            'sent' => $this->messageHasLabel($message, 'SENT'),
            'drafts' => $this->messageHasLabel($message, 'DRAFT'),
            'archive' => ! $this->messageHasLabel($message, 'INBOX')
                && ! $this->messageHasLabel($message, 'TRASH')
                && ! $this->messageHasLabel($message, 'SPAM')
                && ! $this->messageHasLabel($message, 'DRAFT'),
            'all' => true,
            default => $this->messageHasLabel($message, 'INBOX'),
        };
    }

    protected function messageHasLabel(GmailMessage $message, string $label): bool
    {
        $labels = collect($message->labels ?? [])
            ->map(fn ($value) => Str::upper(trim((string) $value)))
            ->filter()
            ->all();

        return in_array(Str::upper($label), $labels, true);
    }

    protected function visibleLabels(array $labels): array
    {
        $ignored = ['UNREAD', 'CATEGORY_PERSONAL', 'CATEGORY_UPDATES', 'CATEGORY_PROMOTIONS', 'IMPORTANT'];

        return collect($labels)
            ->map(fn ($label) => Str::upper((string) $label))
            ->reject(fn ($label) => in_array($label, $ignored, true))
            ->take(3)
            ->values()
            ->all();
    }

    protected function formatThreadDate(?Carbon $date): string
    {
        if (! $date) {
            return 'No date';
        }

        if ($date->isToday()) {
            return $date->format('g:i A');
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        if ($date->year === now()->year) {
            return $date->format('M j');
        }

        return $date->format('M j, Y');
    }

    protected function formatMessageDateTime(?Carbon $date): string
    {
        if (! $date) {
            return 'No date';
        }

        return $date->format('M j, Y g:i A');
    }

    protected function normalizeFolder(): void
    {
        if (! array_key_exists($this->folder, $this->folders)) {
            $this->folder = 'inbox';
        }
    }

    protected function gmailWriteScopesConfigured(): bool
    {
        $scopes = collect((array) config('services.google.workspace_scopes', []))
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->values();

        return $scopes->contains(GoogleGmail::MAIL_GOOGLE_COM)
            || $scopes->contains(GoogleGmail::GMAIL_COMPOSE)
            || $scopes->contains(GoogleGmail::GMAIL_SEND);
    }

    public function render()
    {
        $threadSummaries = $this->threadSummaries;
        $selected = $this->selectedThread;

        return view('livewire.communications.inbox-index', [
            'threadSummaries' => $threadSummaries,
            'selectedThread' => $selected,
            'folderCounts' => $this->folderCounts,
            'hasMessages' => ! empty($threadSummaries),
            'inboxActionLogs' => $this->inboxActionLogs,
        ]);
    }
}
