<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Mapping\Driver;

use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Driver\AttributeDriver;
use Mapado\RestClientSdk\Mapping\Relation;
use Mapado\RestClientSdk\Tests\Model\JsonLd\Cart;
use Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem;
use Mapado\RestClientSdk\Tests\Model\JsonLd\Client;
use Mapado\RestClientSdk\Tests\Model\JsonLd\Invalid;
use Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository;
use Mapado\RestClientSdk\Tests\Model\JsonLd\Product;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AttributeDriver
 */
class AttributeDriverTest extends TestCase
{
    /**
     * testClassWithoutEntityAnnotation
     */
    public function testClassWithoutEntityAnnotation(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname(Client::class);
        $this->assertEmpty($mapping);
    }

    /**
     * testClassWithoutEntityAnnotation
     */
    public function testClassWithInvalidProperty(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $this->expectException(MappingException::class);

        $mapping = $testedInstance->loadClassname(Invalid::class);
    }

    /**
     * testAnnotationDriver
     */
    public function testAnnotationDriver(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname(Product::class);
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf(ClassMetadata::class, $classMetadata);
        $this->assertEquals('product', $classMetadata->getKey());
        $this->assertEquals(Product::class, $classMetadata->getModelName());
        $this->assertEquals(ModelRepository::class, $classMetadata->getRepositoryName());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(3, $attributeList);

        $attribute = current($attributeList);
        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertEquals('id', $attribute->getSerializedKey());

        $attribute = next($attributeList);
        $this->assertEquals('product_value', $attribute->getSerializedKey());
        $this->assertEquals('value', $attribute->getAttributeName());
    }

    public function testAnnotationDriverWithRelations(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname(Cart::class);
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf(ClassMetadata::class, $classMetadata);
        $this->assertEquals('cart', $classMetadata->getKey());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(6, $attributeList);

        $relationList = $classMetadata->getRelationList();
        $this->assertCount(2, $relationList);
        $this->assertEquals(Relation::ONE_TO_MANY, current($relationList)->getType());

        $mapping = $testedInstance->loadClassname(CartItem::class);
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf(ClassMetadata::class, $classMetadata);
        $this->assertEquals('cart_item', $classMetadata->getKey());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(5, $attributeList);

        $relationList = $classMetadata->getRelationList();
        $this->assertCount(1, $relationList);
        $relation = current($relationList);
        $this->assertEquals(Relation::MANY_TO_ONE, $relation->getType());
        $this->assertEquals(Cart::class, $relation->getTargetEntity());
    }

    public function testLoadDirectory(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadDirectory(__DIR__ . '/../../../Model/JsonLd');
        $this->assertCount(4, $mapping);
    }

    /**
     * getCacheDir
     *
     * @return string
     */
    private function getCacheDir()
    {
        return __DIR__ . '/../../../cache/';
    }
}
