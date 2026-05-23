<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\EventSubscriber;

use App\Common\EventSubscriber\RequestSanitizingSubscriber;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RequestSanitizingSubscriberTest extends TestCase
{
    private RequestSanitizingSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new RequestSanitizingSubscriber();
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     * @throws JsonException
     */
    #[DataProvider('queryDataProvider')]
    public function testSanitizesQueryParameters(array $input, array $expected): void
    {
        $request = new Request(query: $input);
        $event = $this->createEvent($request);

        $this->subscriber->onRequest($event);

        static::assertSame($expected, $request->query->all());
    }

    /**
     * @return iterable<string, array{input: array<string, mixed>, expected: array<string, mixed>}>
     */
    public static function queryDataProvider(): iterable
    {
        yield 'trims whitespace' => [
            'input' => ['name' => '  hello  '],
            'expected' => ['name' => 'hello'],
        ];

        yield 'strips HTML tags' => [
            'input' => ['name' => '<script>alert("xss")</script>text'],
            'expected' => ['name' => 'alert("xss")text'],
        ];

        yield 'trims and strips combined' => [
            'input' => ['name' => '  <b>bold</b>  '],
            'expected' => ['name' => 'bold'],
        ];

        yield 'handles nested arrays' => [
            'input' => ['filters' => ['tag' => ' <em>test</em> ']],
            'expected' => ['filters' => ['tag' => 'test']],
        ];

        yield 'preserves non-string values' => [
            'input' => ['page' => '5', 'active' => '1'],
            'expected' => ['page' => '5', 'active' => '1'],
        ];
    }

    /**
     * @throws JsonException
     */
    public function testSanitizesRequestBody(): void
    {
        $request = new Request(request: ['comment' => '  <script>xss</script>safe  ']);
        $event = $this->createEvent($request);

        $this->subscriber->onRequest($event);

        static::assertSame('xsssafe', $request->request->get('comment'));
    }

    /**
     * @throws JsonException
     */
    public function testSanitizesJsonBody(): void
    {
        $json = json_encode(['title' => '  <b>Hello</b>  '], JSON_THROW_ON_ERROR);
        $request = new Request(content: $json);
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createEvent($request);

        $this->subscriber->onRequest($event);

        static::assertSame('Hello', $request->request->get('title'));
    }

    /**
     * @throws JsonException
     */
    public function testIgnoresSubRequests(): void
    {
        $request = new Request(query: ['name' => '  untrimmed  ']);
        $event = $this->createEvent($request, HttpKernelInterface::SUB_REQUEST);

        $this->subscriber->onRequest($event);

        static::assertSame('  untrimmed  ', $request->query->get('name'));
    }

    /**
     * @throws JsonException
     */
    public function testHandlesEmptyJsonBody(): void
    {
        $request = new Request(content: '');
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createEvent($request);

        $this->subscriber->onRequest($event);

        static::assertEmpty($request->request->all());
    }

    private function createEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = $this->createStub(KernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }
}
