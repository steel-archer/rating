<?php

declare(strict_types=1);

namespace App\Enum;

enum DisputeStatus: string
{
    case Created = 'created';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
