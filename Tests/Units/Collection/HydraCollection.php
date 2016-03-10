<?php

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use atoum;

/**
 * SdkClient
 *
 * @uses   atoum
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraCollection extends atoum
{
    /**
     * testCreateCollection
     *
     * @access public
     * @return void
     */
    public function testCreateHydraCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.json'), true);

        $this
            ->given($collection = new \Mapado\RestClientSdk\Collection\HydraCollection($json))

            ->then
            ->object($collection)
            ->isInstanceOf('Mapado\RestClientSdk\Collection\HydraCollection')
            ->isInstanceOf('\Iterator')
            ->hasSize(6)
            ->and
            ->integer($collection->getTotalItems())->isEqualTo(6)
            ->and
            ->array($collection->toArray())->isEqualTo($json['hydra:member']);

    }
}
