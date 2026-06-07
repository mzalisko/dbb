<?php

namespace App\Support;

final class PriceHtml
{
    private const ALLOWED_TAGS = ['span', 'b', 'strong', 'i', 'em', 'small', 'sup', 'sub', 'br'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = strip_tags($html, '<span><b><strong><i><em><small><sup><sub><br>');
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        return trim(preg_replace_callback('/<([a-z0-9]+)([^>]*)>/i', function (array $match): string {
            $tag = strtolower($match[1]);
            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                return '';
            }

            if ($tag === 'br') {
                return '<br>';
            }

            $class = '';
            if (preg_match('/\sclass\s*=\s*(["\'])(.*?)\1/i', $match[2], $classMatch)) {
                $class = trim(preg_replace('/[^A-Za-z0-9 _:-]+/', '', $classMatch[2]) ?? '');
            }

            return $class !== '' ? '<' . $tag . ' class="' . e($class) . '">' : '<' . $tag . '>';
        }, $html) ?? '');
    }

    public static function text(?string $html): string
    {
        $text = html_entity_decode(strip_tags(self::clean($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
