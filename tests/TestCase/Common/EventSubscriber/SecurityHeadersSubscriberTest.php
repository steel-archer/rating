<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\EventSubscriber;

use App\Common\EventSubscriber\SecurityHeadersSubscriber;
use App\Common\Security\CspNonceGenerator;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SecurityHeadersSubscriberTest extends TestCase
{
    /**
     * @param callable(Response): void $assertions
     * @throws Exception
     */
    #[DataProvider('dataProvider')]
    public function testOnResponse(
        string $environment,
        int $requestType,
        ?CspNonceGenerator $nonceGenerator,
        callable $assertions,
    ): void {
        $subscriber = new SecurityHeadersSubscriber(
            $environment,
            $nonceGenerator ?? new CspNonceGenerator(),
        );
        $event = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            $requestType,
            new Response(),
        );

        $subscriber->onResponse($event);

        $assertions($event->getResponse());
    }

    /**
     * @return iterable<string, array{
     *     environment: string,
     *     requestType: int,
     *     nonceGenerator: ?CspNonceGenerator,
     *     assertions: callable(Response): void
     * }>
     * @throws Exception
     */
    public static function dataProvider(): iterable
    {
        yield 'baseline headers are set in non-prod environment' => [
            'environment' => 'test',
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'nonceGenerator' => null,
            'assertions' => static function (Response $response): void {
                $headers = $response->headers;
                static::assertSame('nosniff', $headers->get('X-Content-Type-Options'));
                static::assertSame('DENY', $headers->get('X-Frame-Options'));
                static::assertSame('1; mode=block', $headers->get('X-XSS-Protection'));
                static::assertSame('strict-origin-when-cross-origin', $headers->get('Referrer-Policy'));
                static::assertSame('camera=(), microphone=(), geolocation=()', $headers->get('Permissions-Policy'));
            },
        ];

        yield 'csp and hsts are omitted outside prod' => [
            'environment' => 'test',
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'nonceGenerator' => null,
            'assertions' => static function (Response $response): void {
                static::assertFalse($response->headers->has('Content-Security-Policy'));
                static::assertFalse($response->headers->has('Strict-Transport-Security'));
            },
        ];

        yield 'csp and hsts are set in prod' => [
            'environment' => 'prod',
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'nonceGenerator' => null,
            'assertions' => static function (Response $response): void {
                $headers = $response->headers;
                static::assertSame(
                    'max-age=31536000; includeSubDomains',
                    $headers->get('Strict-Transport-Security'),
                );

                $csp = $headers->get('Content-Security-Policy');
                static::assertNotNull($csp);
                static::assertStringContainsString("default-src 'self'", $csp);
                static::assertStringContainsString("script-src 'self' 'nonce-", $csp);
            },
        ];

        // Regression guard: allowing `data:` in script-src would let attackers run
        // `data:` URI scripts and bypass the nonce protection. The AssetMapper
        // `data:application/javascript,` placeholder must be avoided by keeping CSS
        // out of the JS import graph, never by loosening script-src.
        yield 'prod csp script-src does not allow data uris' => [
            'environment' => 'prod',
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'nonceGenerator' => null,
            'assertions' => static function (Response $response): void {
                $csp = $response->headers->get('Content-Security-Policy');
                static::assertNotNull($csp);
                static::assertSame(
                    1,
                    preg_match('/script-src ([^;]+)/', $csp, $matches),
                    'CSP must declare a script-src directive.',
                );
                static::assertStringNotContainsString('data:', $matches[1]);
            },
        ];

        $nonceGenerator = new CspNonceGenerator();
        $expectedNonce = $nonceGenerator->getNonce();

        yield 'prod csp embeds the generated nonce' => [
            'environment' => 'prod',
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'nonceGenerator' => $nonceGenerator,
            'assertions' => static function (Response $response) use ($expectedNonce): void {
                $csp = $response->headers->get('Content-Security-Policy');
                static::assertNotNull($csp);
                static::assertStringContainsString("'nonce-$expectedNonce'", $csp);
            },
        ];

        yield 'sub-requests are left untouched' => [
            'environment' => 'prod',
            'requestType' => HttpKernelInterface::SUB_REQUEST,
            'nonceGenerator' => null,
            'assertions' => static function (Response $response): void {
                static::assertFalse($response->headers->has('X-Frame-Options'));
                static::assertFalse($response->headers->has('Content-Security-Policy'));
            },
        ];
    }
}
