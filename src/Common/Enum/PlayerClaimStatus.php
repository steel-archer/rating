<?php

declare(strict_types=1);

namespace App\Common\Enum;

enum PlayerClaimStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
