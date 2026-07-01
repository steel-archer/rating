<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TournamentOnlineMode: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Mixed = 'mixed';
}
