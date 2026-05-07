<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'auth_logout')]
    public function __invoke(): never
    {
        // Handled by Symfony security
        throw new LogicException('This should never be reached.');
    }
}
