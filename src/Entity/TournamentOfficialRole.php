<?php

declare(strict_types=1);

namespace App\Entity;

enum TournamentOfficialRole: string
{
    case Organizer = 'organizer';
    case Editor = 'editor';
    case GameJury = 'game_jury';
    case AppealJury = 'appeal_jury';
}
