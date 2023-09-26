<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Model\Serializer;
use Mapado\RestClientSdk\RestClient;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\UnitOfWork;
use PHPUnit\Framework\TestCase;

/**
 * @covers SdkClient
 */
class SdkClientTest extends TestCase
{
    public function testGetRepository(): void
    {
        $restClient = $this->createMock(RestClient::class);

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

        $testedInstance = new SdkClient($restClient, $mapping, $unitOfWork, $serializer);

        $this->assertSame($restClient, $testedInstance->getRestClient());
        $this->assertSame($mapping, $testedInstance->getMapping());
        $this->assertSame($serializer, $testedInstance->getSerializer());

        $this->assertInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository', $testedInstance->getRepository('Mapado\RestClientSdk\Tests\Model\JsonLd\Model'));
        $this->assertInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository', $testedInstance->getRepository('orders'));

        $this->expectException('Mapado\RestClientSdk\Exception\MappingException');
        $testedInstance->getRepository('foo');
    }
}