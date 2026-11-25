<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TournamentDocumentVisibility: string
{
    /** Question package: only hosts of approved sessions, downloads are logged. */
    case Closed = 'closed';

    /** Public material (e.g. regulations): any player, downloads are not logged. */
    case Open = 'open';
}
