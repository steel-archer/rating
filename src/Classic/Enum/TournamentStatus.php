<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
