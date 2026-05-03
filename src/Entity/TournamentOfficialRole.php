<?php

namespace App\Entity;

enum TournamentOfficialRole: string
{
    case Organizer = 'organizer';
    case Editor = 'editor';
    case GameJury = 'game_jury';
    case AppealJury = 'appeal_jury';
}
