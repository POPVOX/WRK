<?php

namespace App\Services;

use App\Models\GmailMessage;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Gmail as GoogleGmail;
use Google\Service\Gmail\Message as GoogleMessage;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class GoogleGmailService
{
    protected GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(GoogleGmail::GMAIL_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function isConnected(User $user): bool
    {
        return ! empty($user->google_access_token);
    }

    public function getGmailService(User $user): ?GoogleGmail
    {
        if (! $this->isConnected($user)) {
            return null;
        }

        if ($user->google_token_expires_at && $user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }

        $this->client->setAccessToken($user->google_access_token);

        return new GoogleGmail($this->client);
    }

    public function syncRecentMessages(User $user, int $daysBack = 30, int $maxMessages = 250): array
    {
        if (! Schema::hasTable('gmail_messages')
            || ! Schema::hasColumn('users', 'gmail_import_date')
            || ! Schema::hasColumn('users', 'gmail_history_id')) {
            throw new RuntimeException('Gmail sync tables are not ready. Run php artisan migrate --force.');
        }

        $service = $this->getGmailService($user);
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

        $query = sprintf('newer_than:%dd -in:chats -in:spam -in:trash', $daysBack);
        $pageToken = null;
        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $latestHistoryId = null;

        try {
            do {
                $remaining = $maxMessages - $processed;
                if ($remaining <= 0) {
                    break;
                }

                $response = $service->users_messages->listUsersMessages('me', array_filter([
                    'maxResults' => min(100, $remaining),
                    'q' => $query,
                    'includeSpamTrash' => false,
                    'pageToken' => $pageToken,
                ]));

                $messageRefs = $response->getMessages() ?? [];
                foreach ($messageRefs as $messageRef) {
                    if ($processed >= $maxMessages) {
                        break;
                    }

                    try {
                        $message = $service->users_messages->get('me', $messageRef->getId(), [
                            'format' => 'metadata',
                            'metadataHeaders' => ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Date'],
                        ]);

                        $historyId = (string) ($message->getHistoryId() ?? '');
                        if ($historyId !== '' && ($latestHistoryId === null || (int) $historyId > (int) $latestHistoryId)) {
                            $latestHistoryId = $historyId;
                        }

                        $payload = $this->mapMessageToRecord($user, $message);
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

                        if ($isNew) {
                            $imported++;
                        } else {
                            $updated++;
                        }
                    } catch (Throwable $exception) {
                        $errors++;
                        \Log::warning('Gmail message sync item failed', [
                            'user_id' => $user->id,
                            'message_id' => $messageRef->getId(),
                            'error' => $exception->getMessage(),
                        ]);
                    } finally {
                        $processed++;
                    }
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken && $processed < $maxMessages);
        } catch (Throwable $exception) {
            $lower = strtolower($exception->getMessage());
            if (str_contains($lower, 'insufficient') && str_contains($lower, 'scope')) {
                throw new RuntimeException(
                    'Google account needs Gmail permission. Disconnect and reconnect Google to grant Gmail access.',
                    previous: $exception
                );
            }

            throw $exception;
        }

        $user->update([
            'gmail_import_date' => now(),
            'gmail_history_id' => $latestHistoryId ?: $user->gmail_history_id,
        ]);

        return [
            'connected' => true,
            'imported' => $imported,
            'updated' => $updated,
            'processed' => $processed,
            'errors' => $errors,
            'history_id' => $latestHistoryId,
        ];
    }

    protected function refreshToken(User $user): void
    {
        if (! $user->google_refresh_token) {
            return;
        }

        $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
        $token = $this->client->getAccessToken();

        if (empty($token['access_token'])) {
            return;
        }

        $user->update([
            'google_access_token' => $token['access_token'],
            'google_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ]);
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
