<?php

namespace App\Services;

class EmailContentFormatter
{
    private const LINK_PATTERN = '~(?<![@\w])((?:https?://|www\.)?[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}(?:/[^\s<>"\']*)?)~iu';

    public function toHtmlDocument(string $text): string
    {
        return '<!doctype html><html><body><div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;white-space:pre-wrap">'.$this->toHtmlFragment($text).'</div></body></html>';
    }

    public function toHtmlFragment(string $text): string
    {
        preg_match_all(self::LINK_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE);
        $html = '';
        $cursor = 0;

        foreach ($matches[1] ?? [] as [$candidate, $offset]) {
            $html .= $this->escape(substr($text, $cursor, $offset - $cursor));
            $display = rtrim($candidate, '.,;:!?)]}');
            $suffix = substr($candidate, strlen($display));
            $href = preg_match('~^https?://~i', $display) === 1 ? $display : 'https://'.$display;
            $html .= '<a href="'.$this->escape($href).'" target="_blank" rel="noopener noreferrer" class="text-indigo-700 underline">'.$this->escape($display).'</a>'.$this->escape($suffix);
            $cursor = $offset + strlen($candidate);
        }

        return $html.$this->escape(substr($text, $cursor));
    }

    /** @return array<int,array{display:string,url:string}> */
    public function links(string $text): array
    {
        preg_match_all(self::LINK_PATTERN, $text, $matches);

        return collect($matches[1] ?? [])
            ->map(function (string $candidate): array {
                $display = rtrim($candidate, '.,;:!?)]}');

                return [
                    'display' => $display,
                    'url' => preg_match('~^https?://~i', $display) === 1 ? $display : 'https://'.$display,
                ];
            })
            ->unique('url')
            ->values()
            ->all();
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
