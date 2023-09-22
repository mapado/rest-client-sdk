<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use PHPUnit\Framework\TestCase;
use Mapado\RestClientSdk\Collection\HalCollection;

/**
 * @covers HalCollection
 */
class HalCollectionTest extends TestCase
{
    /**
     * testCreateHydraPaginatedCollection
     */
    public function testCreateHalCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/orderList.hal.json'), true);

        $collection = new HalCollection($json['_embedded']['ea:order'], $json);

        $this->assertInstanceOf(HalCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertCount(5, $collection->getLinks());
        $this->assertEquals('/orders', $collection->getLinks()['self']['href']);
    }
}