<?php

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use atoum;

/**
 * HydraPaginatedCollection
 *
 * @uses   \atoum
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraPaginatedCollection extends atoum
{
    /**
     * testCreateHydraPaginatedCollection
     */
    public function testCreateHydraPaginatedCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.paginated.json'), true);

        $this
            ->given($collection = new \Mapado\RestClientSdk\Collection\HydraPaginatedCollection($json['hydra:member'], $json))

            ->then
            ->object($collection)
            ->isInstanceOf('Mapado\RestClientSdk\Collection\HydraPaginatedCollection')
            ->isInstanceOf('\Traversable')
            ->hasSize(2)
            ->and
            ->integer($collection->getTotalItems())->isEqualTo(6)
            ->and
            ->array($collection->toArray())->isEqualTo($json['hydra:member'])
            ->and
            ->variable($collection->getFirstPage())->isEqualTo($json['hydra:firstPage'])
            ->and
            ->variable($collection->getLastPage())->isEqualTo($json['hydra:lastPage'])
            ->and
            ->variable($collection->getNextPage())->isEqualTo($json['hydra:nextPage']);
    }
}
