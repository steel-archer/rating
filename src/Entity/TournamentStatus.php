<?php

namespace App\Entity;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
