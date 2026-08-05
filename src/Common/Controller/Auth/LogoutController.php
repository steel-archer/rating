<?php

declare(strict_types=1);

namespace App\Common\Controller\Auth;

use App\Common\Attribute\RateLimited;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[RateLimited('auth')]
#[Route('/logout', name: 'auth_logout', methods: ['POST'])]
class LogoutController extends AbstractController
{
    public function __invoke(): never
    {
        // Handled by Symfony security
        throw new LogicException('This should never be reached.');
    }
}
