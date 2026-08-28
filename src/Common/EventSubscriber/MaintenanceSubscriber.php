<?php

declare(strict_types=1);

namespace App\Common\EventSubscriber;

use App\Common\Service\MaintenanceService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Short-circuits requests with a 503 maintenance page while maintenance mode
 * is enabled. Moderators (and admins, via the role hierarchy) keep full access,
 * and authentication routes stay reachable so a moderator can still log in.
 */
final readonly class MaintenanceSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [
        'auth_google_check',
        'auth_google_start',
        'auth_logout',
    ];

    // Symfony dev toolbar / profiler paths. Security is disabled for these by
    // the "dev" firewall, so no security token is available here — they must be
    // skipped explicitly, never gated behind the maintenance page.
    private const array ALLOWED_PATH_PREFIXES = [
        '/_wdt',
        '/_profiler',
    ];

    public function __construct(
        private Security $security,
        private MaintenanceService $maintenanceService,
        private Environment $twig,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        // Priority 6 runs after the firewall listener (8), so the security
        // token is available when checking roles.
        return [KernelEvents::REQUEST => ['onRequest', 6]];
    }

    /**
     * @throws InvalidArgumentException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->maintenanceService->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        foreach (self::ALLOWED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($pathInfo, $prefix)) {
                return;
            }
        }

        $route = $request->attributes->get('_route');

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return;
        }

        $content = $this->twig->render('maintenance.html.twig');

        $event->setResponse(new Response($content, Response::HTTP_SERVICE_UNAVAILABLE));
    }
}
