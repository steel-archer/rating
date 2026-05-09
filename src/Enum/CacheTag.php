<?php

declare(strict_types=1);

namespace App\Enum;

enum CacheTag: string
{
    case Countries = 'countries';
    case Towns = 'towns';
    case TournamentList = 'tournament_list';
    case Venues = 'venues';

    public static function tournament(int $id): string
    {
        return 'tournament_' . $id;
    }

    public static function player(int $id): string
    {
        return 'player_' . $id;
    }

    public static function team(int $id): string
    {
        return 'team_' . $id;
    }
}
