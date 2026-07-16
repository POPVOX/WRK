<?php

namespace App\Services\Outreach;

use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachRecipientEvent;
use App\Services\EmailContentFormatter;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OutreachTrackingService
{
    public function __construct(protected EmailContentFormatter $formatter) {}

    public function trackedHtml(OutreachCampaignRecipient $recipient, string $plainText): string
    {
        $token = $this->ensureToken($recipient);
        $html = $this->formatter->toHtmlDocument($plainText);
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->getElementsByTagName('a') as $anchor) {
            $destination = trim($anchor->getAttribute('href'));
            if (! $this->validDestination($destination)) {
                continue;
            }
            $anchor->setAttribute('href', route('outreach.track.click', ['token' => $token]).'?url='.rawurlencode($destination));
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if ($body) {
            $pixel = $document->createElement('img');
            $pixel->setAttribute('src', route('outreach.track.open', ['token' => $token]));
            $pixel->setAttribute('width', '1');
            $pixel->setAttribute('height', '1');
            $pixel->setAttribute('alt', '');
            $pixel->setAttribute('style', 'display:block;width:1px;height:1px;border:0;');
            $body->appendChild($pixel);
        }

        return (string) $document->saveHTML();
    }

    public function recordOpen(string $token): void
    {
        $recipient = $this->recipient($token);
        DB::transaction(function () use ($recipient): void {
            $locked = OutreachCampaignRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
            $locked->forceFill([
                'opened_at' => $locked->opened_at ?: now(),
                'open_count' => $locked->open_count + 1,
            ])->save();
            $this->event($locked, 'open');
        });
    }

    public function recordClick(string $token, string $url): string
    {
        if (! $this->validDestination($url)) {
            throw new RuntimeException('Invalid tracking destination.');
        }

        $recipient = $this->recipient($token);
        DB::transaction(function () use ($recipient, $url): void {
            $locked = OutreachCampaignRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
            $locked->forceFill([
                'opened_at' => $locked->opened_at ?: now(),
                'clicked_at' => $locked->clicked_at ?: now(),
                'click_count' => $locked->click_count + 1,
            ])->save();
            $this->event($locked, 'click', $url);
        });

        return $url;
    }

    protected function ensureToken(OutreachCampaignRecipient $recipient): string
    {
        if ($recipient->tracking_token) {
            return $recipient->tracking_token;
        }

        do {
            $token = Str::random(48);
        } while (OutreachCampaignRecipient::query()->where('tracking_token', $token)->exists());

        $recipient->forceFill(['tracking_token' => $token])->save();

        return $token;
    }

    protected function recipient(string $token): OutreachCampaignRecipient
    {
        return OutreachCampaignRecipient::query()
            ->where('tracking_token', $token)
            ->whereNotNull('sent_at')
            ->firstOrFail();
    }

    protected function event(OutreachCampaignRecipient $recipient, string $type, ?string $url = null): void
    {
        OutreachRecipientEvent::query()->create([
            'campaign_recipient_id' => $recipient->id,
            'event_type' => $type,
            'event_key' => hash('sha256', implode('|', [$recipient->id, $type, $url, Str::uuid()])),
            'url' => $url,
            'occurred_at' => now(),
        ]);
    }

    protected function validDestination(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
