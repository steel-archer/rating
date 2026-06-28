<?php

declare(strict_types=1);

namespace App\Common\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', -10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getContentTypeFormat() !== 'json' && !$request->isXmlHttpRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $message = (string) $previous->getViolations()[0]->getMessage();
                $event->setResponse(new JsonResponse(
                    ['error' => $message],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ));
            }
            return;
        }

        $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        $event->setResponse(new JsonResponse(
            ['error' => 'common.error'],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }
}
