<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
