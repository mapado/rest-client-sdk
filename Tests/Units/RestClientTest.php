<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mapado\RestClientSdk\Exception\RestClientException;
use Mapado\RestClientSdk\Exception\RestException;
use Mapado\RestClientSdk\RestClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers RestClient
 */
class RestClientTest extends TestCase
{
    #[DataProvider('getDataProvider')]
    public function testGet(
        string $path,
        Response $mockedResponse,
        string $debugType
    ): void {
        $mock = new MockHandler([$mockedResponse]);

        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $http = new HttpClient(['handler' => $handler]);
        $testedInstance = new RestClient($http);

        $response = $testedInstance->get($path);

        $this->assertSame($debugType, get_debug_type($response));
        $this->assertSame(
            $path,
            array_pop($historyContainer)
                ['request']->getUri()
                ->getPath()
        );
    }

    /**
     * @return iterable<array{string, int, string}>
     */
    public static function getDataProvider(): iterable
    {
        yield [
            '/no-error',
            new Response(
                200,
                [
                    'Content-Type' => 'application/ld+json',
                ],
                file_get_contents(
                    __DIR__ . '/../data/ticketing.list.no_result.json'
                )
            ),
            'array',
        ];

        yield ['/not-found', new Response(404), 'null'];

        yield ['/not-json', new Response(201, []), 'GuzzleHttp\Psr7\Response'];
    }

    #[DataProvider('getExceptionDataProvider')]
    public function testGetException(
        string $path,
        int $responseStatus,
        int $errorCode
    ): void {
        $mock = new MockHandler([new Response($responseStatus)]);

        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $http = new HttpClient(['handler' => $handler]);

        $testedInstance = new RestClient($http);

        $this->expectException('Mapado\RestClientSdk\Exception\RestException');
        $this->expectExceptionMessage('Error while getting resource');
        $this->expectExceptionCode($errorCode);

        try {
            $testedInstance->get($path);
        } catch (RestException $exception) {
            $this->assertInstanceOf(
                ResponseInterface::class,
                $exception->getResponse()
            );
            $this->assertInstanceOf(
                RequestInterface::class,
                $request = $exception->getRequest()
            );
            $this->assertSame($path, $request->getUri()->getPath());

            throw $exception;
        }
    }

    /**
     * @return iterable<array{string, int, int}>
     */
    public static function getExceptionDataProvider(): iterable
    {
        yield ['/error', 502, 1];

        yield ['/403', 403, 7];
    }

    #[DataProvider('deleteDataProvider')]
    public function testDelete(
        int $statusCode,
        ?RestException $expectedException
    ): void {
        $mock = new MockHandler([new Response($statusCode)]);

        $handler = HandlerStack::create($mock);

        $http = new HttpClient(['handler' => $handler]);
        $testedInstance = new RestClient($http);

        if ($expectedException) {
            $this->expectExceptionObject($expectedException);

            $response = $testedInstance->delete('/path');
        } else {
            $this->assertNull($testedInstance->delete('/path'));
        }
    }

    public static function deleteDataProvider(): iterable
    {
        yield [204, null];

        yield [404, null];

        yield [
            500,
            new RestException('Error while deleting resource', '/path', [], 2),
        ];
    }

    #[DataProvider('postDataProvider')]
    public function testPost(
        Response $response,
        ?RestException $expectedException
    ): void {
        $mock = new MockHandler([$response]);

        $handler = HandlerStack::create($mock);

        $http = new HttpClient(['handler' => $handler]);
        $testedInstance = new RestClient($http);
        $params = ['activityUuid' => '63d108be-629c-11e5-b5ce-f153631dac50'];

        if (!$expectedException) {
            $data = $testedInstance->post('/no-error', $params);
            $this->assertIsArray($data);

            $this->assertSame(
                '63d108be-629c-11e5-b5ce-f153631dac50',
                $data['activityUuid']
            );
        } else {
            $this->expectExceptionObject($expectedException);
            $testedInstance->post('/error', $params);
        }
    }

    public static function postDataProvider(): iterable
    {
        yield [
            new Response(
                201,
                [
                    'Content-Type' => 'application/ld+json',
                ],
                file_get_contents(__DIR__ . '/../data/ticketing.created.json')
            ),
            null,
        ];

        yield [
            new Response(400),
            new RestClientException(
                'Cannot create resource',
                '/no-error',
                [],
                3
            ),
        ];

        yield [
            new Response(500),
            new RestException('Error while posting resource', '/error', [], 4),
        ];
    }

    #[DataProvider('putDataProvider')]
    public function testPut(
        Response $response,
        ?RestException $expectedException
    ): void {
        $mock = new MockHandler([$response]);

        $handler = HandlerStack::create($mock);

        $http = new HttpClient(['handler' => $handler]);
        $testedInstance = new RestClient($http);
        $params = ['activityUuid' => 'a9e82f60-629e-11e5-8903-0d978bd11e5d'];

        if (!$expectedException) {
            $data = $testedInstance->put('/no-error', $params);
            $this->assertIsArray($data);
            $this->assertSame(
                'a9e82f60-629e-11e5-8903-0d978bd11e5d',
                $data['activityUuid']
            );
        } else {
            $this->expectExceptionObject($expectedException);
            $testedInstance->put('/error', $params);
        }
    }

    public static function putDataProvider(): iterable
    {
        yield [
            new Response(
                200,
                [
                    'Content-Type' => 'application/ld+json',
                ],
                file_get_contents(__DIR__ . '/../data/ticketing.updated.json')
            ),
            null,
        ];

        yield [
            new Response(404),
            new RestClientException(
                'Cannot update resource',
                '/no-error',
                [],
                5
            ),
        ];

        yield [
            new Response(500),
            new RestException('Error while puting resource', '/error', [], 6),
        ];
    }

    public function testHistory(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents(
                    __DIR__ . '/../data/ticketing.list.no_result.json'
                )
            ),
            new Response(
                200,
                [],
                file_get_contents(
                    __DIR__ . '/../data/ticketing.list.no_result.json'
                )
            ),
            new Response(404),
            new Response(502),
        ]);

        $handler = HandlerStack::create($mock);

        $http = new HttpClient(['handler' => $handler]);
        $testedInstance = new RestClient($http);

        $this->assertFalse($testedInstance->isHistoryLogged());

        $this->assertSame([], $testedInstance->getRequestHistory());

        $testedInstance->get('/');
        $this->assertSame([], $testedInstance->getRequestHistory());

        $testedInstance->setLogHistory(true);
        $this->assertTrue($testedInstance->isHistoryLogged());

        $testedInstance->get('/');
        $this->assertCount(1, $testedInstance->getRequestHistory());

        $testedInstance->get('/');
        $this->assertCount(2, $testedInstance->getRequestHistory());

        try {
            $testedInstance->get('/');
        } catch (RestException $e) {
        }
        $this->assertCount(3, $testedInstance->getRequestHistory());
    }

    #[DataProvider('httpHeadersDataProvider')]
    public function testHttpHeaders(
        Response $response,
        array $headers,
        array $expectedHeaderKeys
    ): void {
        $mock = new MockHandler([$response]);

        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $http = new HttpClient(['handler' => $handler, 'headers' => $headers]);
        $testedInstance = new RestClient($http);

        $result = $testedInstance->get('/no-error');

        $headers = array_pop($historyContainer)['request']->getHeaders();
        $this->assertSame($expectedHeaderKeys, array_keys($headers));
        $this->assertArrayHasKey('@id', $result);
    }

    public static function httpHeadersDataProvider(): iterable
    {
        yield [
            new Response(
                200,
                ['Content-Type' => 'application/ld+json'],
                file_get_contents(
                    __DIR__ . '/../data/ticketing.list.no_result.json'
                )
            ),
            [],
            ['User-Agent'],
        ];

        yield [
            new Response(
                200,
                ['Content-Type' => 'application/ld+json'],
                file_get_contents(
                    __DIR__ . '/../data/ticketing.list.no_result.json'
                )
            ),
            ['Accept-Language' => 'fr'],
            ['Accept-Language', 'User-Agent'],
        ];
    }
}
