<?php

declare(strict_types=1);

namespace App\Enum;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
