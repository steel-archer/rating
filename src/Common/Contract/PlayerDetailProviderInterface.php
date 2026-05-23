<?php

declare(strict_types=1);

namespace App\Common\Contract;

use App\Common\DTO\Response\PlayerDTO;
use App\Common\Exception\EntityNotFoundException;

interface PlayerDetailProviderInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): PlayerDTO;
}
