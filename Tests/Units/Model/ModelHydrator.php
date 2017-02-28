<?php

namespace Mapado\RestClientSdk\Tests\Units\Model;

use atoum;
use DateTime;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;
use Mapado\RestClientSdk\Model\Serializer;

/**
 * Class ModelHydrator
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ModelHydrator extends atoum
{
    private $sdk;

    public function beforeTestMethod($method)
    {
        $fooMetadata = new ClassMetadata(
            'foo',
            'Acme\Foo',
            ''
        );

        $mapping = new Mapping('/v1');
        $mapping->setMapping([
            $fooMetadata,
        ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $this->sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($this->sdk)->getMapping = $mapping;
    }

    /**
     * testConvertId
     *
     * @access public
     * @return void
     */
    public function testConvertId()
    {
        $this
            ->given($this->newTestedInstance($this->sdk))
            ->then
                ->string($this->testedInstance->convertId(2, 'Acme\Foo'))
                    ->isEqualTo('/v1/foo/2')
                ->string($this->testedInstance->convertId('/v1/foo/2', 'Acme\Foo'))
                    ->isEqualTo('/v1/foo/2')
        ;
    }

    /**
     * testConvertIdWithoutMappingPrefix
     *
     * @access public
     * @return void
     */
    public function testConvertIdWithoutMappingPrefix()
    {
        $fooMetadata = new ClassMetadata(
            'foo',
            'Acme\Foo',
            ''
        );

        $mapping = new Mapping();
        $mapping->setMapping([
            $fooMetadata,
        ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($sdk)->getMapping = $mapping;
        $this
            ->given($this->newTestedInstance($sdk))
            ->then
                ->string($this->testedInstance->convertId(2, 'Acme\Foo'))
                    ->isEqualTo('/foo/2')
                ->string($this->testedInstance->convertId('/foo/2', 'Acme\Foo'))
                    ->isEqualTo('/foo/2')
        ;
    }

    public function testHydrateJsonLdItem()
    {
        $productMetadata = new ClassMetadata(
            'product',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Product',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository'
        );
        $productMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('value', 'value', 'string'),
            new Attribute('currency', 'currency', 'string'),
        ]);

        $mapping = new Mapping();
        $mapping->setMapping([
            $productMetadata,
        ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($sdk)->getMapping = $mapping;
        $this->calling($sdk)->getSerializer = new Serializer($mapping);

        $this
            ->given($this->newTestedInstance($sdk))
            // test one json-ld entity
            ->and($productArray = json_decode(file_get_contents(__DIR__ . '/../../data/product.json-ld.json'), true))
            ->then
                ->object($product = $this->testedInstance->hydrate($productArray, 'Mapado\RestClientSdk\Tests\Model\JsonLd\Product'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\Product')
                ->string($product->getId())
                    ->isEqualTo('/products/1')

            // test a json-ld list
            ->and($productListArray = json_decode(file_get_contents(__DIR__ . '/../../data/productList.json-ld.json'), true))
            ->then
                ->object($productList = $this->testedInstance->hydrateList($productListArray, 'Mapado\RestClientSdk\Tests\Model\JsonLd\Product'))
                    ->isInstanceOf('Mapado\RestClientSdk\Collection\HydraPaginatedCollection')
                ->integer($productList->getTotalItems())
                    ->isEqualTo(2)

                ->object($product = $productList[0])
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\Product')
                ->string($product->getId())
                    ->isEqualTo('/products/1')
                ->string($productList[1]->getId())
                    ->isEqualTo('/products/2')
        ;
    }

    public function testHydrateHalItem()
    {
        $orderMetadata = new ClassMetadata(
            'order',
            'Mapado\RestClientSdk\Tests\Model\Hal\Order',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository'
        );
        $orderMetadata->setAttributeList([
            new Attribute('_links.self.href', 'id', 'string', true),
            new Attribute('total', 'total', 'float'),
            new Attribute('currency', 'currency', 'string'),
            new Attribute('status', 'status', 'string'),
        ]);

        $mapping = new Mapping();
        $mapping->setConfig([
            'collectionKey' => '_embedded.ea:order',
        ]);
        $mapping->setMapping([ $orderMetadata ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($sdk)->getMapping = $mapping;
        $this->calling($sdk)->getSerializer = new Serializer($mapping);

        $this
            ->given($this->newTestedInstance($sdk))
            // test one hal entity
            ->and($orderArray = json_decode(file_get_contents(__DIR__ . '/../../data/order.hal.json'), true))
            ->then
                ->object($order = $this->testedInstance->hydrate($orderArray, 'Mapado\RestClientSdk\Tests\Model\Hal\Order'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Hal\Order')
                ->string($order->getStatus())
                    ->isEqualTo('shipped')
                ->string($order->getId())
                    ->isEqualTo('/orders/123')

            // test a json-ld list
            ->and($orderListArray = json_decode(file_get_contents(__DIR__ . '/../../data/orderList.hal.json'), true))
            ->then
                ->object($orderList = $this->testedInstance->hydrateList($orderListArray, 'Mapado\RestClientSdk\Tests\Model\Hal\Order'))
                    ->isInstanceOf('Mapado\RestClientSdk\Collection\HalCollection')
                ->integer($orderList->getTotalItems())
                    ->isEqualTo(2)

                ->object($order = $orderList[0])
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Hal\Order')
                ->string($order->getId())
                    ->isEqualTo('/orders/123')
                ->string($orderList[1]->getId())
                    ->isEqualTo('/orders/124')
        ;
    }
}
