<?php

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Model\Serializer;

/**
 * SdkClient
 *
 * @uses atoum
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class SdkClient extends atoum
{
    /**
     * testGetClient
     *
     * @access public
     * @return void
     */
    public function testGetClient()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $restClient = new \mock\Mapado\RestClientSdk\RestClient();
        $this->mockGenerator->unshuntParentClassCalls();

        $mapping = new RestMapping();
        $mapping->setMapping([
            new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\Model',
                'Mapado\RestClientSdk\Tests\Model\Client'
            )
        ]);

        $serializer = new Serializer($mapping);

        $this
            ->given($testedInstance = $this->newTestedInstance($restClient, $mapping, $serializer))
            ->then
                ->object($testedInstance->getRestClient())
                    ->isIdenticalTo($restClient)

            ->then
                ->object($testedInstance->getMapping())
                    ->isIdenticalTo($mapping)

            ->then
                ->object($testedInstance->getSerializer())
                    ->isIdenticalTo($serializer)

            ->then
                ->object($testedInstance->getClient('orders'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Client')
                ->exception(function () use ($testedInstance) {
                    $testedInstance->getClient('foo');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
        ;
    }
}
