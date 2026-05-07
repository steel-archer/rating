<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/connect/google/check', name: 'auth_google_check')]
class GoogleCheckController extends AbstractController
{
    public function __invoke(): Response
    {
        // Handled by GoogleAuthenticator
        return new Response(status: Response::HTTP_FORBIDDEN);
    }
}
