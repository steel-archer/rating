<?php

declare(strict_types=1);

namespace App\Common\Enum;

enum CacheTag: string
{
    case Countries = 'countries';
    case Towns = 'towns';
    case TournamentList = 'tournament_list';
    case Venues = 'venues';
    case ModerationCounts = 'moderation_counts';

    public static function tournament(int $id): string
    {
        return 'tournament_' . $id;
    }

    /**
     * Tags a player's personal menu action counts.
     */
    public static function menuPlayer(int $id): string
    {
        return 'menu_counts_player_' . $id;
    }

    /**
     * Tags personal menu counts by the tournament that feeds them, so an event
     * in a tournament invalidates the menu badge of every involved organizer/jury.
     */
    public static function menuTournament(int $id): string
    {
        return 'menu_counts_tournament_' . $id;
    }

    public static function player(int $id): string
    {
        return 'player_' . $id;
    }

    public static function playerSquad(int $id): string
    {
        return 'player_squad_' . $id;
    }

    public static function team(int $id): string
    {
        return 'team_' . $id;
    }
}
