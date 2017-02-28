<?php

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use atoum;

/**
 * HalCollection
 *
 * @uses   atoum
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HalCollection extends atoum
{

    /**
     * testCreateHydraPaginatedCollection
     *
     * @access public
     * @return void
     */
    public function testCreateHalCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/orderList.hal.json'), true);

        $this
            ->given($collection = $this->newTestedInstance($json['_embedded']['ea:order'], $json))

            ->then
            ->object($collection)
                ->isInstanceOf('Mapado\RestClientSdk\Collection\HalCollection')
                ->hasSize(2)

            ->and
            ->array($collection->getLinks())
                ->size->isEqualTo(5)

            ->and
            ->string($collection->getLinks()['self']['href'])
                ->isEqualTo('/orders')
        ;
    }
}
