<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Unit\Collection;

use Mapado\RestClientSdk\Collection\HydraPaginatedCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers HydraPaginatedCollection
 */
class HydraPaginatedCollectionTest extends TestCase
{
    /**
     * testCreateHydraPaginatedCollection
     */
    public function testCreateHydraPaginatedCollection(): void
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.paginated.json'), true);

        $collection = new HydraPaginatedCollection($json['hydra:member'], $json);

        $this->assertInstanceOf(HydraPaginatedCollection::class, $collection);
        $this->assertInstanceOf(\Traversable::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertSame(6, $collection->getTotalItems());
        $this->assertSame($json['hydra:member'], $collection->toArray());
        $this->assertSame($json['hydra:firstPage'], $collection->getFirstPage());
        $this->assertSame($json['hydra:lastPage'], $collection->getLastPage());
        $this->assertSame($json['hydra:nextPage'], $collection->getNextPage());
    }
}