<?php

declare(strict_types=1);

namespace App\Common\Helper;

final class NameNormalizer
{
    /**
     * Modifier letter apostrophe (U+02BC) — the canonical apostrophe recommended by Ukrainian
     * orthography. All accepted apostrophe variants are normalized to this character so the same
     * name is always stored and searched with a single, consistent apostrophe.
     */
    public const string CANONICAL_APOSTROPHE = "\u{02BC}";

    /**
     * The apostrophe characters commonly produced by Ukrainian keyboards and typographic input:
     * ASCII apostrophe (U+0027), right single quotation mark (U+2019, Word/iOS) and grave accent
     * (U+0060, sometimes typed by mistake). The canonical U+02BC is intentionally excluded — it
     * needs no replacement.
     */
    private const array APOSTROPHE_VARIANTS = ["\u{0027}", "\u{2019}", "\u{0060}"];

    public static function normalizeApostrophes(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(self::APOSTROPHE_VARIANTS, self::CANONICAL_APOSTROPHE, $value);
    }
}
