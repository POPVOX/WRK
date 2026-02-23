<?php

namespace App\Services\Outreach;

use App\Models\OutreachNewsletter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SubstackDraftBuilder
{
    /**
     * @param  array<int,array{
     *   ts:string,
     *   author:string,
     *   text:string,
     *   links:array<int,string>
     * }>  $messages
     * @param  array{
     *   lead?:?string,
     *   publication_url?:?string,
     *   template_sections?:array<int,string>
     * }  $context
     * @return array{
     *   campaign_name:string,
     *   subject:string,
     *   body_markdown:string,
     *   key_signals:array<int,string>,
     *   link_roundup:array<int,array{url:string,blurb:string,author:string,shared_at:string}>
     * }
     */
    public function build(OutreachNewsletter $newsletter, array $messages, array $context = []): array
    {
        $templateSections = array_values(array_filter(array_map(
            static fn ($section): string => trim((string) $section),
            (array) ($context['template_sections'] ?? [])
        )));

        if ($templateSections === []) {
            $templateSections = [
                'Opening note',
                'Top signals this cycle',
                'Link roundup',
                'Suggested next actions',
            ];
        }

        $signals = $this->extractSignalLines($messages, 6);
        $links = $this->extractLinkRoundup($messages, 10);

        $subjectPrefix = trim((string) ($newsletter->default_subject_prefix ?: $newsletter->name));
        $subject = "{$subjectPrefix} | Draft for ".now()->format('M j, Y');
        $campaignName = "{$newsletter->name} Draft ".now()->format('M j');

        $body = $this->buildMarkdownBody(
            newsletter: $newsletter,
            sections: $templateSections,
            signals: $signals,
            links: $links,
            lead: trim((string) ($context['lead'] ?? '')),
            publicationUrl: trim((string) ($context['publication_url'] ?? ''))
        );

        return [
            'campaign_name' => $campaignName,
            'subject' => $subject,
            'body_markdown' => $body,
            'key_signals' => $signals,
            'link_roundup' => $links,
        ];
    }

    /**
     * @param  array<int,array{ts:string,author:string,text:string,links:array<int,string>}>  $messages
     * @return array<int,string>
     */
    protected function extractSignalLines(array $messages, int $limit): array
    {
        $signals = [];
        $seen = [];

        foreach ($messages as $message) {
            $text = trim((string) ($message['text'] ?? ''));
            if ($text === '' || Str::length($text) < 18) {
                continue;
            }

            $normalized = Str::lower(Str::limit($text, 120, ''));
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;

            $author = trim((string) ($message['author'] ?? 'Teammate'));
            $timestamp = $this->humanTimestamp((string) ($message['ts'] ?? ''));
            $line = "{$author} ({$timestamp}): ".Str::limit($text, 180);
            $signals[] = $line;

            if (count($signals) >= $limit) {
                break;
            }
        }

        return $signals;
    }

    /**
     * @param  array<int,array{ts:string,author:string,text:string,links:array<int,string>}>  $messages
     * @return array<int,array{url:string,blurb:string,author:string,shared_at:string}>
     */
    protected function extractLinkRoundup(array $messages, int $limit): array
    {
        $links = [];
        $seenUrls = [];

        foreach ($messages as $message) {
            $messageLinks = array_values(array_filter((array) ($message['links'] ?? [])));
            if ($messageLinks === []) {
                continue;
            }

            $author = trim((string) ($message['author'] ?? 'Teammate'));
            $sharedAt = $this->humanTimestamp((string) ($message['ts'] ?? ''));
            $blurb = Str::limit(trim((string) ($message['text'] ?? '')), 180);

            foreach ($messageLinks as $url) {
                $url = trim((string) $url);
                if ($url === '' || isset($seenUrls[$url])) {
                    continue;
                }

                $seenUrls[$url] = true;
                $links[] = [
                    'url' => $url,
                    'blurb' => $blurb !== '' ? $blurb : 'Shared in Slack thread.',
                    'author' => $author,
                    'shared_at' => $sharedAt,
                ];

                if (count($links) >= $limit) {
                    break 2;
                }
            }
        }

        return $links;
    }

    /**
     * @param  array<int,string>  $sections
     * @param  array<int,string>  $signals
     * @param  array<int,array{url:string,blurb:string,author:string,shared_at:string}>  $links
     */
    protected function buildMarkdownBody(
        OutreachNewsletter $newsletter,
        array $sections,
        array $signals,
        array $links,
        string $lead,
        string $publicationUrl
    ): string {
        $lines = [];
        $lines[] = '# '.$newsletter->name.' Draft';
        $lines[] = '';
        $lines[] = '_Generated from Slack insights on '.now()->format('M j, Y g:i A').'_';

        if ($publicationUrl !== '') {
            $lines[] = '';
            $lines[] = 'Publication: '.$publicationUrl;
        }

        if ($lead !== '') {
            $lines[] = '';
            $lines[] = 'Lead editor: '.$lead;
        }

        foreach ($sections as $index => $sectionTitle) {
            $sectionNumber = $index + 1;
            $lines[] = '';
            $lines[] = "## {$sectionNumber}. {$sectionTitle}";

            if ($index === 0) {
                $lines[] = 'Draft a concise opening framing why this issue matters this week.';
                continue;
            }

            if ($index === 1) {
                if ($signals === []) {
                    $lines[] = '- No clear Slack signals were detected in the selected time window.';
                } else {
                    foreach ($signals as $signal) {
                        $lines[] = '- '.$signal;
                    }
                }
                continue;
            }

            if (Str::contains(Str::lower($sectionTitle), ['link', 'read', 'resource'])) {
                if ($links === []) {
                    $lines[] = '- No links were found in the selected Slack messages.';
                } else {
                    foreach ($links as $entry) {
                        $url = $entry['url'];
                        $domain = parse_url($url, PHP_URL_HOST) ?: $url;
                        $lines[] = "- [{$domain}]({$url}) — {$entry['blurb']} ({$entry['author']}, {$entry['shared_at']})";
                    }
                }
                continue;
            }

            $lines[] = '- Add 2-3 concise bullets tied to this issue theme.';
        }

        $lines[] = '';
        $lines[] = '## Editorial checklist';
        $lines[] = '- Confirm claims against source links before publication.';
        $lines[] = '- Align tone with POPVOX Foundation voice and style guide.';
        $lines[] = '- Add final CTA and sign-off.';

        return implode("\n", $lines);
    }

    protected function humanTimestamp(string $ts): string
    {
        if ($ts === '') {
            return 'time unknown';
        }

        $parts = explode('.', $ts);
        $seconds = (int) ($parts[0] ?? 0);
        if ($seconds <= 0) {
            return 'time unknown';
        }

        $timezone = date_default_timezone_get() ?: 'UTC';

        return Carbon::createFromTimestampUTC($seconds)
            ->setTimezone($timezone)
            ->format('M j g:i A');
    }
}
