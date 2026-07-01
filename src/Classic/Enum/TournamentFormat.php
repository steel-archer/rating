<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TournamentFormat: string
{
    case Centralized = 'centralized';
    case Distributed = 'distributed';
}
