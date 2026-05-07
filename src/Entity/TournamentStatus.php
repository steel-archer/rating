<?php

declare(strict_types=1);

namespace App\Entity;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
