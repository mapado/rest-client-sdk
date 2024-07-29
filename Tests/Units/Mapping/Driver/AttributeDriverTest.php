<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Mapping\Driver;

use Mapado\RestClientSdk\Mapping\Driver\AttributeDriver;
use Mapado\RestClientSdk\Mapping\Relation;
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

        $mapping = $testedInstance->loadClassname('Mapado\RestClientSdk\Tests\Model\JsonLd\Client');
        $this->assertEmpty($mapping);
    }

    /**
     * testAnnotationDriver
     */
    public function testAnnotationDriver(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname('Mapado\RestClientSdk\Tests\Model\JsonLd\Product');
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf('Mapado\RestClientSdk\Mapping\ClassMetadata', $classMetadata);
        $this->assertEquals('product', $classMetadata->getKey());
        $this->assertEquals('Mapado\RestClientSdk\Tests\Model\JsonLd\Product', $classMetadata->getModelName());
        $this->assertEquals('Mapado\RestClientSdk\Tests\Model\JsonLd\ModelRepository', $classMetadata->getRepositoryName());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(3, $attributeList);

        $attribute = current($attributeList);
        $this->assertInstanceOf('Mapado\RestClientSdk\Mapping\Attribute', $attribute);
        $this->assertEquals('id', $attribute->getSerializedKey());

        $attribute = next($attributeList);
        $this->assertEquals('product_value', $attribute->getSerializedKey());
        $this->assertEquals('value', $attribute->getAttributeName());
    }

    public function testAnnotationDriverWithRelations(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname('Mapado\RestClientSdk\Tests\Model\JsonLd\Cart');
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf('Mapado\RestClientSdk\Mapping\ClassMetadata', $classMetadata);
        $this->assertEquals('cart', $classMetadata->getKey());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(6, $attributeList);

        $relationList = $classMetadata->getRelationList();
        $this->assertCount(2, $relationList);
        $this->assertEquals(Relation::ONE_TO_MANY, current($relationList)->getType());

        $mapping = $testedInstance->loadClassname('Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem');
        $this->assertCount(1, $mapping);

        $classMetadata = current($mapping);
        $this->assertInstanceOf('Mapado\RestClientSdk\Mapping\ClassMetadata', $classMetadata);
        $this->assertEquals('cart_item', $classMetadata->getKey());

        $attributeList = $classMetadata->getAttributeList();
        $this->assertCount(5, $attributeList);

        $relationList = $classMetadata->getRelationList();
        $this->assertCount(1, $relationList);
        $relation = current($relationList);
        $this->assertEquals(Relation::MANY_TO_ONE, $relation->getType());
        $this->assertEquals('Mapado\RestClientSdk\Tests\Model\JsonLd\Cart', $relation->getTargetEntity());
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
