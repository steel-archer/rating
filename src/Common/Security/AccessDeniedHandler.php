<?php

declare(strict_types=1);

namespace App\Common\Security;

use App\Common\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || $user->getPlayer() !== null) {
            return null;
        }

        if (in_array('ROLE_PLAYER', $accessDeniedException->getAttributes(), true)) {
            return new RedirectResponse($this->router->generate('player_claim_index'));
        }

        return null;
    }
}
