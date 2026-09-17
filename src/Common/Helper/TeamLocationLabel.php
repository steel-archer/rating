<?php

declare(strict_types=1);

namespace App\Common\Helper;

final class TeamLocationLabel
{
    /**
     * The home country is omitted from location labels to keep tables compact;
     * only foreign locations are annotated with their country.
     */
    private const string HOME_COUNTRY = 'Україна';

    /**
     * Builds a compact team location label: just the town for home-country
     * teams, or "Town (Country)" for teams based abroad.
     */
    public static function format(?string $townName, ?string $countryName): ?string
    {
        if ($townName === null || $townName === '') {
            return $townName;
        }

        if ($countryName === null || $countryName === '' || $countryName === self::HOME_COUNTRY) {
            return $townName;
        }

        return sprintf('%s (%s)', $townName, $countryName);
    }
}
