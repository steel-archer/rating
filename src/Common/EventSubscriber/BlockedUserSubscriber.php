<?php

declare(strict_types=1);

namespace App\Common\EventSubscriber;

use App\Common\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class BlockedUserSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [
        'auth_google_check',
        'auth_google_start',
        'auth_logout',
        'license',
    ];

    public function __construct(
        private Security $security,
        private Environment $twig,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 6]];
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User || !$user->isBlocked()) {
            return;
        }

        $content = $this->twig->render('blocked.html.twig', [
            'reason' => $user->getBlockedReason(),
        ]);

        $event->setResponse(new Response($content, Response::HTTP_FORBIDDEN));
    }
}
