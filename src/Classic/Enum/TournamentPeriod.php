<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TournamentPeriod: string
{
    case Past = 'past';
    case Active = 'active';
    case Future = 'future';
}
