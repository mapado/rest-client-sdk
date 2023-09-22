<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use PHPUnit\Framework\TestCase;

/**
 * @covers Collection
 */
class CollectionTest extends TestCase
{
    /**
     * testCreateCollection
     */
    public function testCreateCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.json'), true);

        $collection = new \Mapado\RestClientSdk\Collection\Collection($json['hydra:member']);

        $this->assertInstanceOf('Mapado\RestClientSdk\Collection\Collection', $collection);
        $this->assertInstanceOf('\Traversable', $collection);
        $this->assertCount(6, $collection);
        $this->assertEquals(6, $collection->getTotalItems());
        $this->assertEquals($json['hydra:member'], $collection->toArray());
    }

    public function testCreateCollectionWithNoData()
    {
        $collection = new \Mapado\RestClientSdk\Collection\Collection();

        $this->assertInstanceOf('Mapado\RestClientSdk\Collection\Collection', $collection);
        $this->assertCount(0, $collection);
    }

    public function testExtraProperties()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.json'), true);

        $extraProperties = ['foo' => 'bar', 'baz' => 'baz'];
        $collection = new \Mapado\RestClientSdk\Collection\Collection(
            $json['hydra:member'],
            $extraProperties
        );

        $this->assertInstanceOf('Mapado\RestClientSdk\Collection\Collection', $collection);
        $this->assertInstanceOf('\Traversable', $collection);
        $this->assertCount(6, $collection);
        $this->assertEquals(6, $collection->getTotalItems());
        $this->assertEquals($json['hydra:member'], $collection->toArray());

        $this->assertEquals($extraProperties, $collection->getExtraProperties());
        $this->assertEquals('bar', $collection->getExtraProperty('foo'));
        $this->assertNull($collection->getExtraProperty('foobarbaz'));
    }
}