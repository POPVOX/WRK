<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\GmailMessage;
use App\Models\Person;
use App\Models\User;
use App\Services\Agents\AgentCredentialService;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Gmail as GoogleGmail;
use Google\Service\Gmail\Draft as GoogleDraft;
use Google\Service\Gmail\Message as GoogleMessage;
use Google\Service\Gmail\ModifyThreadRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleGmailService
{
    protected GoogleClient $client;

    public function __construct(
        protected AgentCredentialService $agentCredentialService,
        protected CongressionalStaffChangeDetector $congressionalStaffChangeDetector,
        protected EmailContentFormatter $emailContentFormatter
    ) {
        $this->client = new GoogleClient;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $scopes = (array) config('services.google.workspace_scopes', [GoogleGmail::GMAIL_READONLY]);
        foreach (array_values(array_unique(array_filter($scopes))) as $scope) {
            $this->client->addScope($scope);
        }
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function isConnected(User $user, ?Agent $agent = null): bool
    {
        $credential = $this->resolveGoogleCredential($user, $agent);

        return trim((string) ($credential['access_token'] ?? '')) !== '';
    }

    public function getGmailService(User $user, ?Agent $agent = null): ?GoogleGmail
    {
        $credential = $this->resolveGoogleCredential($user, $agent);
        if (trim((string) ($credential['access_token'] ?? '')) === '') {
            return null;
        }

        $expiresAt = $credential['expires_at'] ?? null;
        if ($expiresAt instanceof \Carbon\CarbonInterface && $expiresAt->isPast()) {
            $this->refreshToken($user, $agent, $credential);
            $user->refresh();
            $credential = $this->resolveGoogleCredential($user, $agent);
        }

        if (trim((string) ($credential['access_token'] ?? '')) === '') {
            return null;
        }

        $this->client->setAccessToken((string) ($credential['access_token'] ?? ''));

        return new GoogleGmail($this->client);
    }

    public function syncRecentMessages(User $user, int $daysBack = 30, int $maxMessages = 250, ?Agent $agent = null): array
    {
        if (! Schema::hasTable('gmail_messages')
            || ! Schema::hasColumn('users', 'gmail_import_date')
            || ! Schema::hasColumn('users', 'gmail_history_id')) {
            throw new RuntimeException('Gmail sync tables are not ready. Run php artisan migrate --force.');
        }

        $service = $this->getGmailService($user, $agent);
        if (! $service) {
            return [
                'connected' => false,
                'imported' => 0,
                'updated' => 0,
                'processed' => 0,
                'errors' => 0,
                'history_id' => null,
            ];
        }

        $daysBack = max(1, min($daysBack, 365));
        $maxMessages = max(1, min($maxMessages, 1000));

        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $latestHistoryId = null;

        try {
            $syncBatch = $this->messageIdsForSync($user, $daysBack, $maxMessages, $agent);
            $latestHistoryId = $syncBatch['history_id'];
            foreach ($syncBatch['message_ids'] as $messageId) {
                try {
                    $message = $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($messageId) {
                        return $gmail->users_messages->get('me', $messageId, [
                            'format' => 'metadata',
                            'metadataHeaders' => ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Date'],
                        ]);
                    });

                    $historyId = (string) ($message->getHistoryId() ?? '');
                    if ($historyId !== '' && ($latestHistoryId === null || (int) $historyId > (int) $latestHistoryId)) {
                        $latestHistoryId = $historyId;
                    }

                    $payload = $this->mapMessageToRecord($user, $message);
                    if ($this->congressionalStaffChangeDetector->mightContainSignal(
                        trim(($payload['subject'] ?? '').' '.($payload['snippet'] ?? ''))
                    )) {
                        $fullMessage = $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($messageId) {
                            return $gmail->users_messages->get('me', $messageId, ['format' => 'full']);
                        });
                        $payload['body_text'] = $this->extractMessageBody($fullMessage);
                    }

                    $record = GmailMessage::firstOrNew([
                        'user_id' => $user->id,
                        'gmail_message_id' => $payload['gmail_message_id'],
                    ]);
                    $isNew = ! $record->exists;

                    $record->fill([
                        'person_id' => $payload['person_id'],
                        'gmail_thread_id' => $payload['gmail_thread_id'],
                        'history_id' => $payload['history_id'],
                        'subject' => $payload['subject'],
                        'snippet' => $payload['snippet'],
                        'body_text' => $payload['body_text'] ?? null,
                        'from_email' => $payload['from_email'],
                        'from_name' => $payload['from_name'],
                        'to_emails' => $payload['to_emails'],
                        'cc_emails' => $payload['cc_emails'],
                        'bcc_emails' => $payload['bcc_emails'],
                        'sent_at' => $payload['sent_at'],
                        'is_inbound' => $payload['is_inbound'],
                        'labels' => $payload['labels'],
                    ]);
                    $record->save();
                    $staffChangeSignal = $this->congressionalStaffChangeDetector->detect($record);
                    if ($staffChangeSignal?->status === 'accepted') {
                        $this->archiveProcessedStaffChangeSignal($staffChangeSignal);
                    }
                    app(ContactActivityService::class)->recordGmailMessage($record->loadMissing('user'));

                    if ($isNew) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } catch (Throwable $exception) {
                    $errors++;
                    \Log::warning('Gmail message sync item failed', [
                        'user_id' => $user->id,
                        'message_id' => $messageId,
                        'error' => $exception->getMessage(),
                    ]);
                } finally {
                    $processed++;
                }
            }
        } catch (Throwable $exception) {
            $this->throwIfInsufficientGmailScope($exception);

            if ($this->isAuthError($exception)) {
                throw new RuntimeException($this->googleReconnectMessage(), previous: $exception);
            }

            throw $exception;
        }

        $effectiveHistoryId = $errors === 0
            ? ($latestHistoryId ?: $user->gmail_history_id)
            : $user->gmail_history_id;
        $user->update([
            'gmail_import_date' => now(),
            'gmail_history_id' => $effectiveHistoryId,
        ]);

        return [
            'connected' => true,
            'imported' => $imported,
            'updated' => $updated,
            'processed' => $processed,
            'errors' => $errors,
            'history_id' => $effectiveHistoryId,
            'mode' => $syncBatch['mode'],
        ];
    }

    /**
     * @return array{message_ids:array<int,string>,history_id:?string,mode:string}
     */
    protected function messageIdsForSync(User $user, int $daysBack, int $maxMessages, ?Agent $agent): array
    {
        if (filled($user->gmail_history_id)) {
            try {
                return $this->incrementalMessageIds($user, $maxMessages, $agent);
            } catch (Throwable $exception) {
                if ((int) $exception->getCode() !== 404
                    && ! str_contains(Str::lower($exception->getMessage()), 'starthistoryid')) {
                    throw $exception;
                }

                \Log::notice('Gmail history checkpoint expired; falling back to a bounded recent sync', [
                    'user_id' => $user->id,
                    'history_id' => $user->gmail_history_id,
                ]);
            }
        }

        return $this->recentMessageIds($user, $daysBack, $maxMessages, $agent);
    }

    /**
     * @return array{message_ids:array<int,string>,history_id:?string,mode:string}
     */
    protected function incrementalMessageIds(User $user, int $maxMessages, ?Agent $agent): array
    {
        $pageToken = null;
        $messageIds = collect();
        $checkpoint = (string) $user->gmail_history_id;
        $responseHistoryId = $checkpoint;
        $truncated = false;

        do {
            $response = $this->executeGmailCall($user, $agent, fn (GoogleGmail $gmail) => $gmail->users_history->listUsersHistory('me', array_filter([
                'startHistoryId' => (string) $user->gmail_history_id,
                'historyTypes' => ['messageAdded'],
                'maxResults' => min(500, max(100, $maxMessages)),
                'pageToken' => $pageToken,
            ])));
            $responseHistoryId = (string) ($response->getHistoryId() ?: $responseHistoryId);

            foreach ($response->getHistory() ?? [] as $history) {
                foreach ($history->getMessagesAdded() ?? [] as $addition) {
                    $messageId = (string) ($addition->getMessage()?->getId() ?? '');
                    if ($messageId !== '') {
                        $messageIds->push($messageId);
                    }
                }
                $checkpoint = (string) ($history->getId() ?: $checkpoint);
                if ($messageIds->unique()->count() >= $maxMessages) {
                    $truncated = true;
                    break 2;
                }
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

        return [
            'message_ids' => $messageIds->unique()->values()->all(),
            'history_id' => $truncated || $pageToken ? $checkpoint : $responseHistoryId,
            'mode' => 'history',
        ];
    }

    /**
     * @return array{message_ids:array<int,string>,history_id:?string,mode:string}
     */
    protected function recentMessageIds(User $user, int $daysBack, int $maxMessages, ?Agent $agent): array
    {
        $query = sprintf('newer_than:%dd -in:chats -in:spam -in:trash', $daysBack);
        $pageToken = null;
        $messageIds = collect();

        do {
            $remaining = $maxMessages - $messageIds->count();
            if ($remaining <= 0) {
                break;
            }
            $response = $this->executeGmailCall($user, $agent, fn (GoogleGmail $gmail) => $gmail->users_messages->listUsersMessages('me', array_filter([
                'maxResults' => min(100, $remaining),
                'q' => $query,
                'includeSpamTrash' => false,
                'pageToken' => $pageToken,
            ])));
            foreach ($response->getMessages() ?? [] as $message) {
                $messageId = (string) ($message->getId() ?? '');
                if ($messageId !== '') {
                    $messageIds->push($messageId);
                }
            }
            $pageToken = $response->getNextPageToken();
        } while ($pageToken && $messageIds->count() < $maxMessages);

        return [
            'message_ids' => $messageIds->unique()->values()->all(),
            'history_id' => null,
            'mode' => 'recent',
        ];
    }

    public function sendMessage(
        User $user,
        string|array $to,
        string $subject,
        string $body,
        array $options = [],
        ?Agent $agent = null
    ): array {
        return $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($to, $subject, $body, $options) {
            $message = new GoogleMessage;
            $message->setRaw($this->buildRawMessage($to, $subject, $body, $options));

            $threadId = trim((string) ($options['thread_id'] ?? ''));
            if ($threadId !== '') {
                $message->setThreadId($threadId);
            }

            $sent = $gmail->users_messages->send('me', $message);

            return [
                'message_id' => (string) ($sent->getId() ?? ''),
                'thread_id' => (string) ($sent->getThreadId() ?? ''),
            ];
        });
    }

    public function createDraft(
        User $user,
        string|array $to,
        string $subject,
        string $body,
        array $options = [],
        ?Agent $agent = null
    ): array {
        return $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($to, $subject, $body, $options) {
            $message = new GoogleMessage;
            $message->setRaw($this->buildRawMessage($to, $subject, $body, $options));

            $threadId = trim((string) ($options['thread_id'] ?? ''));
            if ($threadId !== '') {
                $message->setThreadId($threadId);
            }

            $draft = new GoogleDraft;
            $draft->setMessage($message);

            $created = $gmail->users_drafts->create('me', $draft);
            $draftMessage = $created->getMessage();

            return [
                'draft_id' => (string) ($created->getId() ?? ''),
                'message_id' => (string) ($draftMessage?->getId() ?? ''),
                'thread_id' => (string) ($draftMessage?->getThreadId() ?? ''),
            ];
        });
    }

    public function trashThread(User $user, string $threadId, ?Agent $agent = null): array
    {
        $threadId = trim($threadId);
        if ($threadId === '') {
            throw new RuntimeException('No Gmail thread selected.');
        }

        return $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($threadId) {
            $trashed = $gmail->users_threads->trash('me', $threadId);

            return [
                'thread_id' => (string) ($trashed->getId() ?? $threadId),
            ];
        });
    }

    public function archiveThread(User $user, string $threadId, ?Agent $agent = null): array
    {
        $threadId = trim($threadId);
        if ($threadId === '') {
            throw new RuntimeException('No Gmail thread selected.');
        }

        return $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($threadId) {
            $request = new ModifyThreadRequest;
            $request->setRemoveLabelIds(['INBOX', 'UNREAD']);

            $modified = $gmail->users_threads->modify('me', $threadId, $request);

            return [
                'thread_id' => (string) ($modified->getId() ?? $threadId),
                'labels' => array_values(array_filter((array) ($modified->getLabelIds() ?? []))),
            ];
        });
    }

    /**
     * Archive an accepted staff-change message only after its evidence has been
     * committed to WRK. Failures remain retryable by the scheduled reconciler.
     */
    public function archiveProcessedStaffChangeSignal(CongressionalStaffChangeSignal $signal): bool
    {
        if ($signal->status !== 'accepted') {
            return false;
        }

        $evidenceCommitted = Schema::hasTable('congressional_staff_email_events')
            && CongressionalStaffEmailEvent::query()
                ->where('metadata->change_signal_id', $signal->id)
                ->exists();
        if (! $evidenceCommitted) {
            return false;
        }

        $message = $signal->gmailMessage()->with('user')->first();
        if (! $message || $message->automation_processed_at) {
            return (bool) $message?->automation_processed_at;
        }

        $threadId = trim((string) $message->gmail_thread_id);
        $user = $message->user;
        if (! $user || $threadId === '') {
            $message->update([
                'automation_error' => $threadId === ''
                    ? 'The processed Gmail message does not have a thread ID.'
                    : 'The Gmail account owner is no longer available.',
            ]);

            return false;
        }

        if (! $this->isConnected($user)) {
            $message->update([
                'automation_error' => 'Google Workspace must be reconnected before WRK can archive processed messages.',
            ]);

            return false;
        }

        try {
            $this->archiveThread($user, $threadId);

            GmailMessage::query()
                ->where('user_id', $user->id)
                ->where('gmail_thread_id', $threadId)
                ->get()
                ->each(function (GmailMessage $threadMessage): void {
                    $threadMessage->update([
                        'labels' => collect($threadMessage->labels ?? [])
                            ->reject(fn ($label) => in_array(Str::upper(trim((string) $label)), ['INBOX', 'UNREAD'], true))
                            ->values()
                            ->all(),
                        'automation_processed_at' => now(),
                        'automation_disposition' => 'archived_staff_change',
                        'automation_error' => null,
                    ]);
                });

            return true;
        } catch (Throwable $exception) {
            $message->update([
                'automation_error' => Str::limit($exception->getMessage(), 2000),
            ]);

            \Log::warning('Processed congressional Gmail message could not be archived', [
                'gmail_message_id' => $message->id,
                'gmail_thread_id' => $threadId,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function markThreadAsSpam(User $user, string $threadId, ?Agent $agent = null): array
    {
        $threadId = trim($threadId);
        if ($threadId === '') {
            throw new RuntimeException('No Gmail thread selected.');
        }

        return $this->executeGmailCall($user, $agent, function (GoogleGmail $gmail) use ($threadId) {
            $request = new ModifyThreadRequest;
            $request->setAddLabelIds(['SPAM']);
            $request->setRemoveLabelIds(['INBOX', 'UNREAD']);

            $modified = $gmail->users_threads->modify('me', $threadId, $request);

            return [
                'thread_id' => (string) ($modified->getId() ?? $threadId),
                'labels' => array_values(array_filter((array) ($modified->getLabelIds() ?? []))),
            ];
        });
    }

    /**
     * @param  array<string,mixed>|null  $credential
     */
    protected function refreshToken(User $user, ?Agent $agent = null, ?array $credential = null): bool
    {
        $credential ??= $this->resolveGoogleCredential($user, $agent);
        $refreshToken = trim((string) ($credential['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            return false;
        }

        $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (! is_array($token)) {
            $token = $this->client->getAccessToken();
        }

        if (! empty($token['error']) || empty($token['access_token'])) {
            \Log::warning('Google token refresh failed for Gmail', [
                'user_id' => $user->id,
                'agent_id' => $agent?->id,
                'error' => $token['error'] ?? 'missing_access_token',
                'error_description' => $token['error_description'] ?? null,
            ]);

            if (! $agent && ($token['error'] ?? null) === 'invalid_grant') {
                $user->forceFill([
                    'google_access_token' => null,
                    'google_refresh_token' => null,
                    'google_token_expires_at' => null,
                ])->save();
            }

            return false;
        }

        if ($agent) {
            $existing = $this->agentCredentialService->getCredential($agent, 'gmail');
            $existingTokenData = is_array($existing?->token_data) ? $existing->token_data : [];
            $tokenData = array_merge($existingTokenData, [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? $refreshToken,
                'token_type' => $token['token_type'] ?? ($existingTokenData['token_type'] ?? 'Bearer'),
            ]);

            $this->agentCredentialService->storeCredential(
                $agent,
                'gmail',
                $tokenData,
                is_array($existing?->scopes) ? $existing->scopes : [],
                now()->addSeconds((int) ($token['expires_in'] ?? 3600))
            );

            return true;
        }

        $user->update([
            'google_access_token' => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
            'google_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ]);

        return true;
    }

    /**
     * @return array{
     *   source:string,
     *   access_token:string,
     *   refresh_token:string,
     *   expires_at:Carbon|null
     * }
     */
    protected function resolveGoogleCredential(User $user, ?Agent $agent = null): array
    {
        if ($agent) {
            $agentCredential = $this->agentCredentialService->getCredential($agent, 'gmail');
            $tokenData = is_array($agentCredential?->token_data) ? $agentCredential->token_data : [];
            $expiresAt = $agentCredential?->expires_at;

            if (! $expiresAt && ! empty($tokenData['expires_at'])) {
                try {
                    $expiresAt = Carbon::parse((string) $tokenData['expires_at']);
                } catch (\Throwable) {
                    $expiresAt = null;
                }
            }

            return [
                'source' => 'agent',
                'access_token' => trim((string) ($tokenData['access_token'] ?? '')),
                'refresh_token' => trim((string) ($tokenData['refresh_token'] ?? '')),
                'expires_at' => $expiresAt,
            ];
        }

        return [
            'source' => 'user',
            'access_token' => trim((string) $user->google_access_token),
            'refresh_token' => trim((string) $user->google_refresh_token),
            'expires_at' => $user->google_token_expires_at,
        ];
    }

    protected function mapMessageToRecord(User $user, GoogleMessage $message): array
    {
        $headers = $this->extractHeaderMap($message);
        $from = $this->parseEmailAddress((string) ($headers['from'] ?? ''));
        $to = $this->parseEmailList((string) ($headers['to'] ?? ''));
        $cc = $this->parseEmailList((string) ($headers['cc'] ?? ''));
        $bcc = $this->parseEmailList((string) ($headers['bcc'] ?? ''));

        $fromEmail = strtolower((string) ($from['email'] ?? ''));
        $userEmail = strtolower((string) $user->email);
        $isInbound = $fromEmail !== '' && $fromEmail !== $userEmail;

        $counterpart = $this->resolveCounterpartEmail($isInbound, $fromEmail, $to, $cc, $bcc, $userEmail);
        $personId = null;
        if ($counterpart !== null) {
            $personId = Person::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($counterpart)])
                ->value('id');
        }

        return [
            'user_id' => $user->id,
            'person_id' => $personId ? (int) $personId : null,
            'gmail_message_id' => (string) $message->getId(),
            'gmail_thread_id' => (string) ($message->getThreadId() ?? ''),
            'history_id' => (string) ($message->getHistoryId() ?? ''),
            'subject' => $this->decodeMimeHeader((string) ($headers['subject'] ?? '')),
            'snippet' => (string) ($message->getSnippet() ?? ''),
            'body_text' => null,
            'from_email' => $fromEmail !== '' ? $fromEmail : null,
            'from_name' => (string) ($from['name'] ?? '') !== '' ? (string) $from['name'] : null,
            'to_emails' => $to,
            'cc_emails' => $cc,
            'bcc_emails' => $bcc,
            'sent_at' => $this->parseSentAt($headers['date'] ?? null, $message->getInternalDate()),
            'is_inbound' => $isInbound,
            'labels' => array_values(array_filter((array) ($message->getLabelIds() ?? []))),
        ];
    }

    protected function extractMessageBody(GoogleMessage $message): ?string
    {
        $plain = [];
        $html = [];
        $this->collectMessagePartText($message->getPayload(), $plain, $html);
        $parts = $plain !== [] ? $plain : array_map(
            fn (string $value) => html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $html
        );
        $body = trim(implode("\n\n", array_filter($parts)));

        return $body !== '' ? Str::limit($body, 50000, '') : null;
    }

    protected function collectMessagePartText(mixed $part, array &$plain, array &$html): void
    {
        if (! $part) {
            return;
        }

        $mimeType = strtolower((string) ($part->getMimeType() ?? ''));
        $data = (string) ($part->getBody()?->getData() ?? '');
        if ($data !== '' && in_array($mimeType, ['text/plain', 'text/html'], true)) {
            $decoded = base64_decode(strtr($data, '-_', '+/'), true);
            if ($decoded !== false) {
                if ($mimeType === 'text/plain') {
                    $plain[] = $decoded;
                } else {
                    $html[] = $decoded;
                }
            }
        }

        foreach ((array) ($part->getParts() ?? []) as $child) {
            $this->collectMessagePartText($child, $plain, $html);
        }
    }

    protected function buildRawMessage(string|array $to, string $subject, string $body, array $options = []): string
    {
        $toList = $this->normalizeRecipients($to);
        if ($toList === []) {
            throw new RuntimeException('At least one valid To recipient is required.');
        }

        $subject = trim($subject);
        if ($subject === '') {
            throw new RuntimeException('Subject is required.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Message body is required.');
        }

        $ccList = $this->normalizeRecipients($options['cc'] ?? []);
        $bccList = $this->normalizeRecipients($options['bcc'] ?? []);

        $boundary = 'wrk_'.substr(hash('sha256', $subject."\n".$body), 0, 32);
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
            'To: '.implode(', ', $toList),
            'Subject: '.$this->encodeMimeHeader($subject),
        ];

        if ($ccList !== []) {
            $headers[] = 'Cc: '.implode(', ', $ccList);
        }

        if ($bccList !== []) {
            $headers[] = 'Bcc: '.implode(', ', $bccList);
        }

        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
        $normalizedBody = preg_replace("/\n{3,}/", "\n\n", $normalizedBody) ?? $normalizedBody;
        $htmlBody = trim((string) ($options['html_body'] ?? '')) ?: $this->emailContentFormatter->toHtmlDocument($normalizedBody);
        $parts = [
            '--'.$boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $normalizedBody,
            '--'.$boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $htmlBody,
            '--'.$boundary.'--',
        ];

        $mime = implode("\r\n", $headers)."\r\n\r\n".implode("\r\n", $parts);

        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
    }

    protected function normalizeRecipients(string|array|null $value): array
    {
        $parts = [];
        if (is_array($value)) {
            $parts = $value;
        } elseif (is_string($value)) {
            $split = preg_split('/[\n;,]+/', $value) ?: [];
            $parts = $split;
        }

        $formatted = [];
        foreach ($parts as $part) {
            $parsed = $this->parseEmailAddress((string) $part);
            $email = trim((string) ($parsed['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $name = trim((string) ($parsed['name'] ?? ''));
            if ($name !== '') {
                $safeName = str_replace(['"', "\r", "\n"], '', $name);
                $formatted[] = sprintf('"%s" <%s>', $safeName, $email);
            } else {
                $formatted[] = $email;
            }
        }

        return array_values(array_unique($formatted));
    }

    protected function encodeMimeHeader(string $value): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            $encoded = @mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }

        return $value;
    }

    protected function throwIfInsufficientGmailScope(Throwable $exception): void
    {
        $lower = strtolower($exception->getMessage());
        if (str_contains($lower, 'insufficient') && str_contains($lower, 'scope')) {
            throw new RuntimeException(
                'Google account needs updated Gmail permissions for reading, sending, and archiving. Disconnect and reconnect Google Workspace, then try again.',
                previous: $exception
            );
        }
    }

    protected function executeGmailCall(User $user, ?Agent $agent, callable $callback): mixed
    {
        $service = $this->getGmailService($user, $agent);
        if (! $service) {
            throw new RuntimeException('Google account is not connected.');
        }

        try {
            return $callback($service);
        } catch (Throwable $exception) {
            $this->throwIfInsufficientGmailScope($exception);

            if ($this->isAuthError($exception) && $this->refreshToken($user, $agent)) {
                $user->refresh();
                $retryService = $this->getGmailService($user, $agent);
                if ($retryService) {
                    return $callback($retryService);
                }
            }

            if ($this->isAuthError($exception)) {
                throw new RuntimeException($this->googleReconnectMessage(), previous: $exception);
            }

            throw $exception;
        }
    }

    protected function isAuthError(Throwable $exception): bool
    {
        $code = (int) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($code, [401, 403], true)
            || str_contains($message, 'invalid credentials')
            || str_contains($message, 'autherror')
            || str_contains($message, 'unauthenticated')
            || str_contains($message, 'login required');
    }

    protected function googleReconnectMessage(): string
    {
        return 'Google authentication expired or was revoked. Disconnect and reconnect Google Workspace, then run Gmail sync again.';
    }

    protected function extractHeaderMap(GoogleMessage $message): array
    {
        $headers = [];
        $payload = $message->getPayload();
        foreach ((array) ($payload?->getHeaders() ?? []) as $header) {
            $name = strtolower((string) $header->getName());
            if ($name === '') {
                continue;
            }

            $headers[$name] = (string) $header->getValue();
        }

        return $headers;
    }

    protected function parseSentAt(?string $dateHeader, $internalDate): ?Carbon
    {
        try {
            if (is_string($dateHeader) && trim($dateHeader) !== '') {
                return Carbon::parse($dateHeader);
            }
        } catch (Throwable) {
            // Fall through.
        }

        if (is_numeric($internalDate)) {
            $timestampMs = (int) $internalDate;
            if ($timestampMs > 0) {
                return Carbon::createFromTimestamp((int) floor($timestampMs / 1000));
            }
        }

        return null;
    }

    protected function resolveCounterpartEmail(
        bool $isInbound,
        string $fromEmail,
        array $to,
        array $cc,
        array $bcc,
        string $userEmail
    ): ?string {
        if ($isInbound && $fromEmail !== '') {
            return $fromEmail;
        }

        foreach ([$to, $cc, $bcc] as $group) {
            foreach ($group as $email) {
                $normalized = strtolower(trim((string) $email));
                if ($normalized === '' || $normalized === $userEmail) {
                    continue;
                }

                return $normalized;
            }
        }

        return null;
    }

    protected function parseEmailList(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/,(?=(?:[^"]*"[^"]*")*[^"]*$)/', $value) ?: [];
        $emails = [];
        foreach ($parts as $part) {
            $parsed = $this->parseEmailAddress($part);
            $email = strtolower(trim((string) ($parsed['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }

    protected function parseEmailAddress(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['name' => null, 'email' => null];
        }

        $name = null;
        $email = null;

        if (preg_match('/^(.*)<([^>]+)>$/', $value, $matches)) {
            $name = trim((string) $matches[1], " \t\n\r\0\x0B\"'");
            $email = trim((string) $matches[2]);
        } else {
            $email = trim($value, "\"'<> ");
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['name' => null, 'email' => null];
        }

        return [
            'name' => $name ? $this->decodeMimeHeader($name) : null,
            'email' => strtolower($email),
        ];
    }

    protected function decodeMimeHeader(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        if (function_exists('mb_decode_mimeheader')) {
            $decoded = @mb_decode_mimeheader($value);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $value;
    }
}
