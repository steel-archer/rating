<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Attribute\RateLimited;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class RateLimitSubscriber implements EventSubscriberInterface
{
    /**
     * @param ServiceLocator<RateLimiterFactory> $rateLimiters
     */
    public function __construct(
        private ServiceLocator $rateLimiters,
        private TokenStorageInterface $tokenStorage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $attributes = $event->getAttributes(RateLimited::class);

        if ($attributes === []) {
            return;
        }

        $limiterName = $attributes[0]->limiter;

        if (!$this->rateLimiters->has($limiterName)) {
            return;
        }

        /** @var RateLimiterFactory $factory */
        $factory = $this->rateLimiters->get($limiterName);

        $request = $event->getRequest();
        $identity = $this->resolveIdentity($request);
        $limiter = $factory->create($limiterName . ':' . $identity);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $this->logger->warning('Rate limit exceeded', [
                'limiter' => $limiterName,
                'ip' => $request->getClientIp(),
                'route' => $request->attributes->get('_route'),
                'user' => $identity,
            ]);

            $retryAfter = max(0, $limit->getRetryAfter()->getTimestamp() - time());

            $event->setController(static function () use ($request, $retryAfter): Response {
                $headers = ['Retry-After' => $retryAfter, 'X-RateLimit-Remaining' => 0];

                if (
                    $request->getContentTypeFormat() === 'json'
                    || $request->isXmlHttpRequest()
                ) {
                    return new JsonResponse(
                        ['error' => 'common.rate_limit_exceeded'],
                        Response::HTTP_TOO_MANY_REQUESTS,
                        $headers,
                    );
                }

                return new Response(
                    'Too Many Requests',
                    Response::HTTP_TOO_MANY_REQUESTS,
                    $headers,
                );
            });
        }
    }

    private function resolveIdentity(Request $request): string
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if ($user instanceof UserInterface) {
            return $user->getUserIdentifier();
        }

        return $request->getClientIp() ?? 'unknown';
    }
}
