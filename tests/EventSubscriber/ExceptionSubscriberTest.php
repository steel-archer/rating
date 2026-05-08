<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ExceptionSubscriberTest extends TestCase
{
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new ExceptionSubscriber($this->logger);
    }

    public function testLogsAndReturnsJsonForUnhandledException(): void
    {
        $exception = new RuntimeException('Something broke');
        $request = new Request(content: '{}');
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createEvent($request, $exception);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Something broke', static::arrayHasKey('exception'));

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        static::assertNotNull($response);
        static::assertSame(500, $response->getStatusCode());
        static::assertSame('{"error":"common.error"}', $response->getContent());
    }

    public function testIgnoresHttpExceptions(): void
    {
        $exception = new NotFoundHttpException('Not found');
        $request = new Request(content: '{}');
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createEvent($request, $exception);

        $this->logger->expects(static::never())->method('error');

        $this->subscriber->onException($event);

        static::assertNull($event->getResponse());
    }

    public function testIgnoresNonJsonRequests(): void
    {
        $exception = new RuntimeException('Error');
        $request = new Request();
        $request->headers->set('Content-Type', 'text/html');

        $event = $this->createEvent($request, $exception);

        $this->logger->expects(static::never())->method('error');

        $this->subscriber->onException($event);

        static::assertNull($event->getResponse());
    }

    public function testIgnoresSubRequests(): void
    {
        $exception = new RuntimeException('Error');
        $request = new Request(content: '{}');
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createEvent($request, $exception, HttpKernelInterface::SUB_REQUEST);

        $this->logger->expects(static::never())->method('error');

        $this->subscriber->onException($event);

        static::assertNull($event->getResponse());
    }

    public function testHandlesXmlHttpRequest(): void
    {
        $exception = new RuntimeException('Ajax error');
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $event = $this->createEvent($request, $exception);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Ajax error', static::arrayHasKey('exception'));

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        static::assertNotNull($response);
        static::assertSame(500, $response->getStatusCode());
    }

    private function createEvent(
        Request $request,
        \Throwable $exception,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ExceptionEvent {
        $kernel = $this->createStub(KernelInterface::class);

        return new ExceptionEvent($kernel, $request, $requestType, $exception);
    }
}
