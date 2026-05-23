<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum DisputeStatus: string
{
    case Created = 'created';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
