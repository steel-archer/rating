<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\EventSubscriber;

use App\Common\EventSubscriber\RateLimitSubscriber;
use App\Tests\Fixtures\Controller\RateLimitedApiSuggestController;
use App\Tests\Fixtures\Controller\RateLimitedMutationController;
use App\Tests\Fixtures\Controller\RateLimitedUnknownController;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RateLimitSubscriberTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('blockedRequestDataProvider')]
    public function testBlocksWhenRateLimitExceeded(
        array $headers,
        string $expectedContent,
    ): void {
        $request = new Request();
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Rate limit exceeded', static::arrayHasKey('limiter'));

        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->once())->method('has')->with('mutation')->willReturn(true);
        $rateLimiters->expects($this->once())->method('get')->with('mutation')
            ->willReturn($this->createLimiterFactory(accepted: false));

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $logger,
        );

        $event = $this->createEvent(new RateLimitedMutationController(), $request);
        $subscriber->onController($event);

        $response = ($event->getController())();
        static::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        static::assertTrue($response->headers->has('Retry-After'));
        static::assertStringContainsString($expectedContent, $response->getContent());
    }

    /**
     * @return iterable<string, array{headers: array<string, string>, expectedContent: string}>
     */
    public static function blockedRequestDataProvider(): iterable
    {
        yield 'json request returns JSON error' => [
            'headers' => ['Content-Type' => 'application/json'],
            'expectedContent' => 'rate_limit_exceeded',
        ];

        yield 'XmlHttpRequest returns JSON error' => [
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
            'expectedContent' => 'rate_limit_exceeded',
        ];

        yield 'plain request returns text' => [
            'headers' => ['Content-Type' => 'text/html'],
            'expectedContent' => 'Too Many Requests',
        ];
    }

    public function testAllowsRequestWhenLimitNotExceeded(): void
    {
        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->once())->method('has')->with('api_suggest')->willReturn(true);
        $rateLimiters->expects($this->once())->method('get')->with('api_suggest')
            ->willReturn($this->createLimiterFactory(accepted: true));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $logger,
        );

        $event = $this->createEvent(new RateLimitedApiSuggestController(), new Request());
        $subscriber->onController($event);

        $response = ($event->getController())();
        static::assertSame(200, $response->getStatusCode());
    }

    public function testSkipsWhenNoAttribute(): void
    {
        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->never())->method('has');

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $this->createStub(LoggerInterface::class),
        );

        $controller = static fn(): Response => new Response('OK');
        $event = $this->createEvent($controller, new Request());
        $subscriber->onController($event);

        $response = ($event->getController())();
        static::assertSame(200, $response->getStatusCode());
    }

    public function testSkipsSubRequests(): void
    {
        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->never())->method('has');

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $this->createStub(LoggerInterface::class),
        );

        $event = $this->createEvent(
            new RateLimitedMutationController(),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
        );
        $subscriber->onController($event);

        $response = ($event->getController())();
        static::assertSame(200, $response->getStatusCode());
    }

    public function testSkipsWhenLimiterNotRegistered(): void
    {
        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->once())->method('has')->with('unknown')->willReturn(false);
        $rateLimiters->expects($this->never())->method('get');

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $this->createStub(LoggerInterface::class),
        );

        $event = $this->createEvent(new RateLimitedUnknownController(), new Request());
        $subscriber->onController($event);

        $response = ($event->getController())();
        static::assertSame(200, $response->getStatusCode());
    }

    public function testUsesUserIdentifierAsKey(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);

        $factory->expects($this->once())->method('create')->with('mutation:user@example.com')->willReturn($limiter);
        $limiter->expects($this->once())->method('consume')->willReturn($rateLimit);
        $rateLimit->expects($this->once())->method('isAccepted')->willReturn(true);

        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->once())->method('has')->with('mutation')->willReturn(true);
        $rateLimiters->expects($this->once())->method('get')->with('mutation')->willReturn($factory);

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $tokenStorage,
            $this->createStub(LoggerInterface::class),
        );

        $event = $this->createEvent(new RateLimitedMutationController(), new Request());
        $subscriber->onController($event);
    }

    public function testUsesIpAsKeyForAnonymous(): void
    {
        $request = Request::create('http://localhost', server: ['REMOTE_ADDR' => '192.168.1.1']);

        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);

        $factory->expects($this->once())->method('create')->with('api_suggest:192.168.1.1')->willReturn($limiter);
        $limiter->expects($this->once())->method('consume')->willReturn($rateLimit);
        $rateLimit->expects($this->once())->method('isAccepted')->willReturn(true);

        $rateLimiters = $this->createMock(ServiceLocator::class);
        $rateLimiters->expects($this->once())->method('has')->with('api_suggest')->willReturn(true);
        $rateLimiters->expects($this->once())->method('get')->with('api_suggest')->willReturn($factory);

        $subscriber = new RateLimitSubscriber(
            $rateLimiters,
            $this->createTokenStorage(),
            $this->createStub(LoggerInterface::class),
        );

        $event = $this->createEvent(new RateLimitedApiSuggestController(), $request);
        $subscriber->onController($event);
    }

    private function createLimiterFactory(bool $accepted): RateLimiterFactoryInterface
    {
        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);

        $rateLimit->expects($this->once())->method('isAccepted')->willReturn($accepted);

        if (!$accepted) {
            $rateLimit->expects($this->once())
                ->method('getRetryAfter')
                ->willReturn(new DateTimeImmutable('+60 seconds'));
        }

        $limiter->expects($this->once())->method('consume')->willReturn($rateLimit);
        $factory->expects($this->once())->method('create')->willReturn($limiter);

        return $factory;
    }

    private function createTokenStorage(): TokenStorageInterface
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        return $tokenStorage;
    }

    private function createEvent(
        callable $controller,
        Request $request,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ControllerEvent {
        $kernel = $this->createStub(KernelInterface::class);

        return new ControllerEvent($kernel, $controller, $request, $requestType);
    }
}
