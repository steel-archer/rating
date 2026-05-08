<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use JsonException;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestSanitizingSubscriber implements EventSubscriberInterface
{
    #[CodeCoverageIgnore]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 128]];
    }

    /**
     * @throws JsonException
     */
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $request->query->replace($this->sanitize($request->query->all()));
        $request->request->replace($this->sanitize($request->request->all()));

        if ($request->getContentTypeFormat() === 'json') {
            $content = $request->getContent();

            if ($content !== '') {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    $request->request->replace($this->sanitize($decoded));
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_string($value)) {
                return trim(strip_tags($value));
            }

            if (is_array($value)) {
                return $this->sanitize($value);
            }

            return $value;
        }, $data);
    }
}
