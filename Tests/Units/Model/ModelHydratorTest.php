<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Model;

use PHPUnit\Framework\TestCase;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Model\ModelHydrator;
use Mapado\RestClientSdk\Model\Serializer;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\UnitOfWork;

/**
 * @covers ModelHydrator
 */
class ModelHydratorTest extends TestCase
{
    private $sdk;

    private $unitOfWork;

    public function setUp(): void
    {
        parent::setUp();

        $fooMetadata = new ClassMetadata("foo", "Acme\Foo", "");

        $mapping = new Mapping("/v1");
        $mapping->setMapping([$fooMetadata]);

        $this->unitOfWork = new UnitOfWork($mapping);

        $this->sdk = $this->createStub(\Mapado\RestClientSdk\SdkClient::class);
        $this->sdk->method("getMapping")->willReturn($mapping);
    }

    public function testConvertId()
    {
        $testedInstance = new ModelHydrator($this->sdk);

        $this->assertEquals(
            "/v1/foo/2",
            $testedInstance->convertId(2, "Acme\Foo")
        );
        $this->assertEquals(
            "/v1/foo/2",
            $testedInstance->convertId("/v1/foo/2", "Acme\Foo")
        );
    }

    public function testConvertIdWithoutMappingPrefix()
    {
        $fooMetadata = new ClassMetadata("foo", "Acme\Foo", "");

        $mapping = new Mapping();
        $mapping->setMapping([$fooMetadata]);

        $sdk = $this->createMock(SdkClient::class);
        $sdk->method("getMapping")->willReturn($mapping);

        $testedInstance = new ModelHydrator($sdk);

        $this->assertEquals(
            "/foo/2",
            $testedInstance->convertId(2, "Acme\Foo")
        );
        $this->assertEquals(
            "/foo/2",
            $testedInstance->convertId("/foo/2", "Acme\Foo")
        );
    }

    public function testHydrateJsonLdItem()
    {
        $productMetadata = new ClassMetadata(
            "product",
            "Mapado\RestClientSdk\Tests\Model\JsonLd\Product",
            "Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository"
        );
        $productMetadata->setAttributeList([
            new Attribute("@id", "id", "string", true),
            new Attribute("value", "value", "string"),
            new Attribute("currency", "currency", "string"),
        ]);

        $mapping = new Mapping();
        $mapping->setMapping([$productMetadata]);

        $sdk = $this->createMock(SdkClient::class);
        $sdk->method("getMapping")->willReturn($mapping);
        $sdk->method("getSerializer")->willReturn(
            new Serializer($mapping, $this->unitOfWork)
        );

        $testedInstance = new ModelHydrator($sdk);
        // test one json-ld entity
        $productArray = json_decode(
            file_get_contents(__DIR__ . "/../../data/product.json-ld.json"),
            true
        );

        $product = $testedInstance->hydrate(
            $productArray,
            "Mapado\RestClientSdk\Tests\Model\JsonLd\Product"
        );

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Tests\Model\JsonLd\Product",
            $product
        );

        $this->assertEquals("/products/1", $product->getId());

        // test a json-ld list
        $productListArray = json_decode(
            file_get_contents(__DIR__ . "/../../data/productList.json-ld.json"),
            true
        );

        $productList = $testedInstance->hydrateList(
            $productListArray,
            "Mapado\RestClientSdk\Tests\Model\JsonLd\Product"
        );

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Collection\HydraPaginatedCollection",
            $productList
        );

        $this->assertSame(2, $productList->getTotalItems());

        $product = $productList[0];

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Tests\Model\JsonLd\Product",
            $product
        );
        $this->assertSame("/products/1", $product->getId());

        $this->assertSame("/products/2", $productList[1]->getId());
    }

    public function testHydrateHalItem()
    {
        $orderMetadata = new ClassMetadata(
            "order",
            "Mapado\RestClientSdk\Tests\Model\Hal\Order",
            "Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository"
        );
        $orderMetadata->setAttributeList([
            new Attribute("_links.self.href", "id", "string", true),
            new Attribute("total", "total", "float"),
            new Attribute("currency", "currency", "string"),
            new Attribute("status", "status", "string"),
        ]);

        $mapping = new Mapping();

        $mapping->setConfig([
            "collectionKey" => "_embedded.ea:order",
        ]);
        $mapping->setMapping([$orderMetadata]);

        $sdk = $this->createMock(SdkClient::class);
        $sdk->method("getMapping")->willReturn($mapping);
        $sdk->method("getSerializer")->willReturn(
            new Serializer($mapping, $this->unitOfWork)
        );

        $testedInstance = new ModelHydrator($sdk);

        // test one hal entity
        $orderArray = json_decode(
            file_get_contents(__DIR__ . "/../../data/order.hal.json"),
            true
        );
        $order = $testedInstance->hydrate(
            $orderArray,
            "Mapado\RestClientSdk\Tests\Model\Hal\Order"
        );

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Tests\Model\Hal\Order",
            $order
        );
        $this->assertSame("shipped", $order->getStatus());
        $this->assertSame("/orders/123", $order->getId());

        // test a json-ld list
        $orderListArray = json_decode(
            file_get_contents(__DIR__ . "/../../data/orderList.hal.json"),
            true
        );
        $orderList = $testedInstance->hydrateList(
            $orderListArray,
            "Mapado\RestClientSdk\Tests\Model\Hal\Order"
        );

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Collection\HalCollection",
            $orderList
        );
        $this->assertSame(2, $orderList->getTotalItems());

        $order = $orderList[0];

        $this->assertInstanceOf(
            "Mapado\RestClientSdk\Tests\Model\Hal\Order",
            $order
        );

        $this->assertSame("/orders/123", $order->getId());
        $this->assertSame("/orders/124", $orderList[1]->getId());
    }
}
