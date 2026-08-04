<?php

declare(strict_types=1);

namespace App\Common\EventSubscriber;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestSanitizingSubscriber implements EventSubscriberInterface
{
    /**
     * @codeCoverageIgnore
     */
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

        $request->query->replace(self::sanitize($request->query->all()));
        $request->request->replace(self::sanitize($request->request->all()));

        if ($request->getContentTypeFormat() === 'json') {
            $content = $request->getContent();

            if ($content !== '') {
                $decoded = json_decode($content, true, 10, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    $request->request->replace(self::sanitize($decoded));
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        return array_map(static function (mixed $value): mixed {
            if (is_string($value)) {
                return trim(self::stripTags($value));
            }

            if (is_array($value)) {
                return self::sanitize($value);
            }

            return $value;
        }, $data);
    }

    /**
     * Unlike strip_tags(), this only removes sequences that actually look like HTML tags
     * (a `<` immediately followed by a letter or `/`, with no nested `<`/`>` and a real closing `>`).
     * strip_tags() deletes everything from an unmatched `<` to the end of the string, and also
     * deletes any `<...>` span even when it isn't HTML (e.g. "a < b" or "<Encyclopedia Britannica>"),
     * which silently destroyed legitimate text such as math comparisons.
     */
    private static function stripTags(string $value): string
    {
        // Note: a non-HTML span that merely looks like a tag (e.g. "<Encyclopedia Britannica>")
        // still matches and gets stripped; that narrower edge case is intentionally out of scope here.
        return preg_replace('/<\/?[a-zA-Z][^<>]*>/', '', $value) ?? $value;
    }
}
