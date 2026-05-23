<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

interface HasContactFields
{
    public function getTelegram(): ?string;

    public function getFacebook(): ?string;

    public function getPhone(): ?string;
}
