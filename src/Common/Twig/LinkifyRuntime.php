<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

class LinkifyRuntime implements RuntimeExtensionInterface
{
    private const CHARSET = 'UTF-8';

    /**
     * Turns plain user text into safe HTML: the whole string is HTML-escaped first,
     * then http/https URLs are wrapped in anchors and newlines become <br>.
     * Because escaping happens before linkification, no user-supplied markup can survive.
     */
    public function linkify(?string $text): Markup
    {
        if ($text === null || $text === '') {
            return new Markup('', self::CHARSET);
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, self::CHARSET);

        // Trailing punctuation is excluded from the link so sentences like "see https://x.dev." stay clean.
        $linked = preg_replace_callback(
            '#https?://[^\s<]+#',
            static function (array $matches): string {
                $url = $matches[0];
                $trailing = '';

                while ($url !== '' && str_contains('.,;:!?)]}\'"', substr($url, -1))) {
                    $trailing = substr($url, -1) . $trailing;
                    $url = substr($url, 0, -1);
                }

                if ($url === '') {
                    return $trailing;
                }

                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer nofollow">%s</a>%s',
                    $url,
                    $url,
                    $trailing,
                );
            },
            $escaped,
        ) ?? $escaped;

        return new Markup(nl2br($linked, false), self::CHARSET);
    }
}
