<?php

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\Stream\Stream;

/**
 * Class RestClient
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestClient extends atoum
{
    /**
     * testGet
     *
     * @access public
     * @return void
     */
    public function testGet()
    {
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [
                        'Content-Type' => 'application/ld+json'
                    ],
                    file_get_contents(__DIR__ . '/../data/ticketing.list.no_result.json')
                ),
                new Response(404),
                new Response(502),
                new Response(
                    201,
                    []
                ),
                new Response(403),
            ]
        );

        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $this
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance($http))
            ->then
                ->array($this->testedInstance->get('/no-error'))
                ->string(array_pop($historyContainer)['request']->getUri()->getPath())
                    ->isEqualTo('/no-error')

            ->then
                ->variable($this->testedInstance->get('/not-found'))
                    ->isNull()

            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance) {
                    $testedInstance->get('/error');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestException')
                    ->hasMessage('Error while getting resource')
                    ->hasCode(1)

            // test binary get
            ->then
                ->object($this->testedInstance->get('/not-json'))
                    ->isInstanceOf('\GuzzleHttp\Psr7\Response')

            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance) {
                    $testedInstance->get('/403');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestException')
                    ->hasMessage('Error while getting resource')
                    ->hasCode(7)
           ;
    }

    /**
     * testDelete
     *
     * @access public
     * @return void
     */
    public function testDelete()
    {
        $mock = new MockHandler(
            [
                new Response(204),
                new Response(404),
                new Response(500),
            ]
        );

        $handler = HandlerStack::create($mock);

        $this
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance($http))
            ->then
                ->variable($this->testedInstance->delete('/no-error'))
                    ->isNull()

            ->then
                ->variable($this->testedInstance->delete('/not-found'))
                    ->isNull()

            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance) {
                    $testedInstance->delete('/error');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestException')
                    ->hasMessage('Error while deleting resource')
                    ->hasCode(2)
           ;
    }

    /**
     * testPost
     *
     * @access public
     * @return void
     */
    public function testPost()
    {
        $mock = new MockHandler(
            [
                new Response(
                    201,
                    [
                        'Content-Type' => 'application/ld+json'
                    ],
                    file_get_contents(__DIR__ . '/../data/ticketing.created.json')
                ),
                new Response(400),
                new Response(500),
            ]
        );

        $handler = HandlerStack::create($mock);

        $this
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance($http))
                ->and($params = ['activityUuid' => '63d108be-629c-11e5-b5ce-f153631dac50'])
            ->then
                ->array(
                    $data = $this->testedInstance->post('/no-error', $params)
                )
                ->then
                    ->string($data['activityUuid'])
                        ->isEqualTo('63d108be-629c-11e5-b5ce-f153631dac50')
            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance, $params) {
                    $testedInstance->post('/not-found', $params);
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestClientException')
                    ->hasMessage('Cannot create resource')
                    ->hasCode(3)

            ->then
                ->exception(function () use ($testedInstance, $params) {
                    $testedInstance->post('/error', $params);
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestException')
                    ->hasMessage('Error while posting resource')
                    ->hasCode(4)
        ;
    }

    /**
     * testPut
     *
     * @access public
     * @return void
     */
    public function testPut()
    {
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [
                        'Content-Type' => 'application/ld+json'
                    ],
                    file_get_contents(__DIR__ . '/../data/ticketing.updated.json')
                ),
                new Response(404),
                new Response(500),
            ]
        );

        $handler = HandlerStack::create($mock);

        $this
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance($http))
                ->and($params = ['activityUuid' => 'a9e82f60-629e-11e5-8903-0d978bd11e5d'])
            ->then
                ->array(
                    $data = $this->testedInstance->put('/no-error', $params)
                )
                ->then
                    ->string($data['activityUuid'])
                        ->isEqualTo('a9e82f60-629e-11e5-8903-0d978bd11e5d')
            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance, $params) {
                    $testedInstance->put('/not-found', $params);
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestClientException')
                    ->hasMessage('Cannot update resource')
                    ->hasCode(5)

            ->then
                ->exception(function () use ($testedInstance, $params) {
                    $testedInstance->put('/error', $params);
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\RestException')
                    ->hasMessage('Error while puting resource')
                    ->hasCode(6)
        ;
    }

    public function testHistory()
    {
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/../data/ticketing.list.no_result.json')
                ),
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/../data/ticketing.list.no_result.json')
                ),
                new Response(404),
                new Response(502),
            ]
        );

        $handler = HandlerStack::create($mock);

        $this
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance($http))
            ->then
                ->boolean($this->testedInstance->isHistoryLogged())
                    ->isFalse()

            ->then
                ->array($this->testedInstance->getRequestHistory())
                    ->isEmpty()

            ->if($this->testedInstance->get('/'))
            ->then
                ->array($this->testedInstance->getRequestHistory())
                    ->isEmpty()

            ->then
                ->if($this->testedInstance->setLogHistory(true))
                    ->boolean($this->testedInstance->isHistoryLogged())
                        ->isTrue()

            ->if($this->testedInstance->get('/'))
            ->then
                ->array($this->testedInstance->getRequestHistory())
                    ->isNotEmpty()
                    ->size->isEqualTo(1)

            ->if($this->testedInstance->get('/'))
            ->then
                ->array($this->testedInstance->getRequestHistory())
                    ->isNotEmpty()
                    ->size->isEqualTo(2)

            ->if($testedInstance = $this->testedInstance)
            ->and
                ->exception(function () use ($testedInstance) {
                    $testedInstance->get('/');
                })
            ->then
                ->array($this->testedInstance->getRequestHistory())
                    ->size->isEqualTo(3)
        ;
    }

    public function testHttpHeaders()
    {
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [ 'Content-Type' => 'application/ld+json' ],
                    file_get_contents(__DIR__ . '/../data/ticketing.list.no_result.json')
                ),

                new Response(
                    200,
                    [ 'Content-Type' => 'application/ld+json' ],
                    file_get_contents(__DIR__ . '/../data/ticketing.list.no_result.json')
                ),
            ]
        );

        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $this
            // no headers
            ->given($http = new HttpClient(['handler' => $handler]))
                ->and($this->newTestedInstance(
                    $http
                ))
            ->then($this->testedInstance->get('/no-error'))
                ->and($headers = array_pop($historyContainer)['request']->getHeaders())
            ->array($headers)
                ->notHasKey('Accept-Language')

            // with headers
            ->given($http = new HttpClient(['handler' => $handler, 'headers' => ['Accept-Language' => 'fr']]))
                ->and($this->newTestedInstance(
                    $http
                ))
            ->then($this->testedInstance->get('/no-error'))
                ->and($headers = array_pop($historyContainer)['request']->getHeaders())
            ->array($headers)
                ->hasKey('Accept-Language')
        ;
    }
}
