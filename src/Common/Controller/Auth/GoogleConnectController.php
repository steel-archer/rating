<?php

declare(strict_types=1);

namespace App\Common\Controller\Auth;

use App\Common\Attribute\RateLimited;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[RateLimited('auth')]
#[Route('/connect/google', name: 'auth_google_start')]
class GoogleConnectController extends AbstractController
{
    public function __invoke(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile']);
    }
}
