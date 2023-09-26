<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers Mapping
 */
class MappingTest extends TestCase
{
    /**
     * @covers ::getModelName
     * @dataProvider getModelNameDataProvider
     */
    public function testGetModelName(array $mapping, string $key, ?string $expectedException = null, ?string $expectedMessage = null, ?string $expectedModelName = null): void
    {
        $mappingInstance = new Mapping();
        $mappingInstance->setMapping($mapping);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        if ($expectedModelName !== null) {
            $this->assertSame($expectedModelName, $mappingInstance->getModelName($key));
        } else {
            $mappingInstance->getModelName($key);
        }
    }

    public static function getModelNameDataProvider(): array
    {
        return [
            'no key given' => [
                [new ClassMetadata('foo', 'foo', null)],
                '',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'key is not set',
                null,
            ],
            'no mapping found' => [
                [new ClassMetadata('foo', 'foo', null)],
                'orders',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'orders key is not mapped',
                null,
            ],
            'wrong mapping array' => [
                [new ClassMetadata('orders', '', null)],
                'orders',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'orders key is mapped but the model name is empty',
                null,
            ],
            'model found' => [
                self::getMappingArray(),
                'orders',
                null,
                null,
                'Foo\Bar\Model\Order',
            ],
        ];
    }

    /**
     * @covers ::tryGetClassMetadataById
     */
    public function testTryGetClassMetadataById(): void
    {
        $classMetadata = new ClassMetadata(
            'bars',
            'Foo\Entity\Bar',
            'Foo\Repository\BarRepository'
        );

        $mappingInstance = new Mapping();
        $mappingInstance->setMapping([$classMetadata]);

        // no mapping found
        $this->assertSame(null, $mappingInstance->tryGetClassMetadataById('unknown'));

        // model found
        $this->assertSame($classMetadata, $mappingInstance->tryGetClassMetadataById('/bars/1234'));
    }

    /**
     * @covers ::getMappingKeys
     */
    public function testGetMappingKeys(): void
    {
        $mappingInstance = new Mapping();

        $this->assertSame([], $mappingInstance->getMappingKeys());

        $mappingInstance ->setMapping(self::getMappingArray());

        $this->assertSame(['orders', 'order_items', 'clients'], $mappingInstance->getMappingKeys());
    }

    /**
     * @covers ::getKeyFromId
     * @dataProvider getKeyFromIdDataProvider
     */
    public function testGetKeyFromId(array $mapping, string $id, ?string $expectedException = null, ?string $expectedMessage = null, ?string $expectedKey = null): void
    {
        $mappingInstance = new Mapping();
        $mappingInstance->setMapping($mapping);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        if ($expectedKey !== null) {
            $this->assertSame($expectedKey, $mappingInstance->getKeyFromId($id));
        } else {
            $mappingInstance->getKeyFromId($id);
        }
    }

    public static function getKeyFromIdDataProvider(): array
    {
        return [
            'no mappings' => [
                [],
                '/orders/8',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'orders key is not mapped',
                null,
            ],
            'good instances' => [
                self::getMappingArray(),
                '/orders/8',
                null,
                null,
                'orders',
            ],
            'a really complicated id' => [
                self::getMappingArray(),
                '/sales/customers/3/orders/8',
                null,
                null,
                'orders',
            ],
        ];
    }

    /**
     * @covers ::getKeyFromId
     */
    public function testPrefix(): void
    {
        $mappingInstance = new Mapping('/v1');
        $mappingInstance->setMapping(self::getMappingArray());

        $this->assertSame('orders', $mappingInstance->getKeyFromId('/v1/orders/8'));
    }

    /**
     * @covers ::getKeyFromModel
     * @dataProvider getKeyFromModelDataProvider
     */
    public function testGetKeyFromModel( string $modelName, ?string $expectedException = null, ?string $expectedMessage = null, ?string $expectedKey = null): void
    {
        $mappingInstance = new Mapping();
        $mappingInstance->setMapping(self::getMappingArray());

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        if ($expectedKey !== null) {
            $this->assertSame($expectedKey, $mappingInstance->getKeyFromModel($modelName));
        } else {
            $mappingInstance->getKeyFromModel($modelName);
        }
    }

    public static function getKeyFromModelDataProvider(): array
    {
        return [
            'model found' => [
                'Foo\Bar\Model\OrderItem',
                null,
                null,
                'order_items',
            ],
            'model not found' => [
                '\Not\Viable\Classname',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'Model name \Not\Viable\Classname not found in mapping',
                null,
            ],
        ];
    }

    /**
     * @covers ::getClassMetadata
     * @dataProvider getClassMetadataDataProvider
     */
    public function testGetClassMetadata(string $modelName, ?string $expectedException = null, ?string $expectedMessage = null, ?ClassMetadata $expectedClassMetadata = null): void
    {
        $mappingInstance = new Mapping();
        $mappingInstance->setMapping(self::getMappingArray());

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        if ($expectedClassMetadata !== null) {
            $this->assertEquals($expectedClassMetadata, $mappingInstance->getClassMetadata($modelName));
        } else {
            $mappingInstance->getClassMetadata($modelName);
        }
    }

    public static function getClassMetadataDataProvider(): array
    {
        $order = new ClassMetadata(
            'orders',
            'Foo\Bar\Model\Order',
            'Foo\Bar\Client\OrderClient'
        );

        $client = new ClassMetadata(
            'clients',
            'Foo\Bar\Model\Client',
            'Foo\Bar\Client\ClientClient'
        );

        return [
            'order model found' => [
                'Foo\Bar\Model\Order',
                null,
                null,
                $order,
            ],
            'client model found' => [
                'Foo\Bar\Model\Client',
                null,
                null,
                $client,
            ],
            'model not found' => [
                'Foo\Bar',
                \Mapado\RestClientSdk\Exception\MappingException::class,
                'Foo\Bar model is not mapped',
                null,
            ],
        ];
    }

    /**
     * @covers ::hasClassMetadata
     * @dataProvider hasClassMetadataDataProvider
     */
    public function testHasClassMetadata( string $modelName, bool $expectedResult): void
    {
        $mappingInstance = new Mapping();
        $mappingInstance->setMapping(self::getMappingArray());

        $this->assertSame($expectedResult, $mappingInstance->hasClassMetadata($modelName));
    }

    public static function hasClassMetadataDataProvider(): array
    {
        return [
            'model found' => [
                'Foo\Bar\Model\Order',
                true,
            ],
            'model not found' => [
                'Foo\Bar',
                false,
            ],
        ];
    }

    /**
     * testMappingConfiguration
     *
     * @covers ::getConfig
     * @dataProvider mappingConfigurationDataProvider
     */
    public function testMappingConfiguration(array $config, array $expectedResult): void
    {
        $mappingInstance = new Mapping('', $config);

        $this->assertSame($expectedResult, $mappingInstance->getConfig());
    }

    public static function mappingConfigurationDataProvider(): array
    {
        return [
            'default configuration' => [
                [],
                [
                    'collectionKey' => 'hydra:member',
                ],
            ],
            'custom configuration' => [
                [
                    'collectionKey' => 'collection',
                ],
                [
                    'collectionKey' => 'collection',
                ],
            ],
        ];
    }

    /**
     * @return ClassMetadata[]
     */
    private static function getMappingArray(): array
    {
        $order = new ClassMetadata(
            'orders',
            'Foo\Bar\Model\Order',
            'Foo\Bar\Client\OrderClient'
        );

        $orderItem = new ClassMetadata(
            'order_items',
            'Foo\Bar\Model\OrderItem',
            'Foo\Bar\Client\OrderItemClient'
        );

        $client = new ClassMetadata(
            'clients',
            'Foo\Bar\Model\Client',
            'Foo\Bar\Client\ClientClient'
        );

        return [$order, $orderItem, $client];
    }
}