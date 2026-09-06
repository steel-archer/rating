<?php

declare(strict_types=1);

namespace App\Common\Sentry;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Scrubs sensitive data from every event before it leaves for Sentry.
 *
 * Wired as the `before_send` (and `before_send_transaction`) callback in
 * config/packages/sentry.yaml. With send_default_pii enabled the SDK collects
 * request cookies, headers and bodies verbatim, so this callback masks the
 * parts that must never reach a third-party service: session/auth cookies,
 * credential headers and personal contact fields submitted in request bodies.
 */
final class SentryEventSanitizer
{
    private const string MASK = '[Filtered]';

    /**
     * Request/POST keys whose values are secrets or personal data. Matched
     * case-insensitively against a substring of the key, so "access_token"
     * is covered by "token" and "user_password" by "password".
     */
    private const array SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'api_key',
        'apikey',
        'phone',
        'email',
        'telegram',
        'facebook',
    ];

    /**
     * Request headers dropped entirely regardless of their value.
     */
    private const array SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    public function __invoke(Event $event, ?EventHint $hint = null): Event
    {
        $request = $event->getRequest();

        if ($request !== []) {
            $event->setRequest($this->sanitizeRequest($request));
        }

        return $event;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     */
    private function sanitizeRequest(array $request): array
    {
        // Cookies always carry the session id and remember-me token: drop them.
        unset($request['cookies']);

        // The query string may embed tokens; it is rarely useful for triage.
        unset($request['query_string']);

        if (isset($request['headers']) && \is_array($request['headers'])) {
            $request['headers'] = $this->sanitizeHeaders($request['headers']);
        }

        if (isset($request['data'])) {
            $request['data'] = $this->sanitizeValue($request['data']);
        }

        return $request;
    }

    /**
     * @param array<array-key, mixed> $headers
     *
     * @return array<array-key, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (\in_array(strtolower((string) $name), self::SENSITIVE_HEADERS, true)) {
                $headers[$name] = self::MASK;
            }
        }

        return $headers;
    }

    /**
     * Recursively masks values whose key looks sensitive.
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (\is_string($key) && $this->isSensitiveKey($key)) {
                $value[$key] = self::MASK;

                continue;
            }

            $value[$key] = $this->sanitizeValue($item);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
