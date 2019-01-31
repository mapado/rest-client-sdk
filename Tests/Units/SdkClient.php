<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Model\Serializer;
use Mapado\RestClientSdk\UnitOfWork;

/**
 * SdkClient
 *
 * @uses \atoum
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class SdkClient extends atoum
{
    /**
     * testGetRepository
     */
    public function testGetRepository()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $restClient = new \mock\Mapado\RestClientSdk\RestClient();
        $this->mockGenerator->unshuntParentClassCalls();

        $mapping = new RestMapping();
        $mapping->setMapping([
            new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Model',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository'
            ),
        ]);

        $unitOfWork = new UnitOfWork($mapping);
        $serializer = new Serializer($mapping, $unitOfWork);

        $this
            ->given($testedInstance = $this->newTestedInstance($restClient, $mapping, $unitOfWork, $serializer))
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
                ->object($testedInstance->getRepository('Mapado\RestClientSdk\Tests\Model\JsonLd\Model'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository')

                ->object($testedInstance->getRepository('orders'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository')

                ->exception(function () use ($testedInstance) {
                    $testedInstance->getRepository('foo');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
        ;
    }
}
