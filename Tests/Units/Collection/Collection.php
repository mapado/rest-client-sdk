<?php

namespace Mapado\RestClientSdk\Tests\Units\Collection;

use atoum;
use Mapado\RestClientSdk\Collection\Collection as TestedClass;

/**
 * Collection
 *
 * @uses   atoum
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class Collection extends atoum
{
    /**
     * testCreateCollection
     *
     * @access public
     * @return void
     */
    public function testCreateCollection()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.json'), true);

        $this
            ->given($collection = new \Mapado\RestClientSdk\Collection\Collection($json['hydra:member']))

            ->then
            ->object($collection)
            ->isInstanceOf('Mapado\RestClientSdk\Collection\Collection')
            ->isInstanceOf('\Traversable')
            ->hasSize(6)
            ->and
            ->integer($collection->getTotalItems())->isEqualTo(6)
            ->and
            ->array($collection->toArray())->isEqualTo($json['hydra:member'])
            ;
    }

    public function testCreateCollectionWithNoData()
    {
        $this
            ->given($collection = new \Mapado\RestClientSdk\Collection\Collection())

            ->then
            ->object($collection)
            ->isInstanceOf('Mapado\RestClientSdk\Collection\Collection')
            ->hasSize(0)
        ;
    }

    public function testExtraProperties()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/ticketing.list.json'), true);

        $extraProperties = ['foo' => 'bar', 'baz' => 'baz'];
        $this
            ->given($this->newTestedInstance(
                $json['hydra:member'],
                $extraProperties
            ))
            ->object($this->testedInstance)
                ->isInstanceOf('Mapado\RestClientSdk\Collection\Collection')
                ->isInstanceOf('\Traversable')
                ->hasSize(6)
            ->and
                ->integer($this->testedInstance->getTotalItems())->isEqualTo(6)
            ->and
                ->array($this->testedInstance->toArray())->isEqualTo($json['hydra:member'])

            ->and
                ->array($this->testedInstance->getExtraProperties())
                    ->isEqualTo($extraProperties)

                ->string($this->testedInstance->getExtraProperty('foo'))
                    ->isEqualTo('bar')

                ->variable($this->testedInstance->getExtraProperty('foobarbaz'))
                    ->isNull()
        ;
    }
}
