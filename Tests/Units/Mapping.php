<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * Class Mapping
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Mapping extends atoum
{
    /**
     * testGetModelName
     */
    public function testGetModelName()
    {
        $this
            // no key given
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping([new ClassMetadata('foo', 'foo', null)]))
            ->then($testedInstance = $this->testedInstance)
            ->exception(function () use ($testedInstance) {
                $testedInstance->getModelName('');
            })
                ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                ->hasMessage('key is not set')

            // no mapping found
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping([new ClassMetadata('foo', 'foo', null)]))
            ->then($testedInstance = $this->testedInstance)
            ->exception(function () use ($testedInstance) {
                $testedInstance->getModelName('orders');
            })
                ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                ->hasMessage('orders key is not mapped')

            // wrong mapping array
            ->given($this->newTestedInstance)
            ->and($this->testedInstance->setMapping([new ClassMetadata('orders', '', null)]))
            ->then($testedInstance = $this->testedInstance)
            ->exception(function () use ($testedInstance) {
                $testedInstance->getModelName('orders');
            })
                ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                ->hasMessage('orders key is mapped but the model name is empty')

            // model found
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->string($this->testedInstance->getModelName('orders'))
                ->isEqualTo('Foo\Bar\Model\Order')
        ;
    }

    /**
     * testTryGetClassMetadataById
     */
    public function testTryGetClassMetadataById()
    {
        $this
            // no mapping found
            ->given($this->newTestedInstance)
                ->and(
                    $this->testedInstance->setMapping(
                        [
                            $classMetadata = new ClassMetadata(
                                'bars',
                                'Foo\Entity\Bar',
                                'Foo\Repository\BarRepository'
                            ),
                        ]
                    )
                )
            ->when($result = $this->testedInstance->tryGetClassMetadataById('unknown'))
            ->then
                ->variable($result)
                    ->isNull()

            // model found
            ->when($result = $this->testedInstance->tryGetClassMetadataById('/bars/1234'))
            ->then
                ->variable($result)
                    ->isIdenticalTo($classMetadata)
        ;
    }

    /**
     * testGetMappingKeys
     */
    public function testGetMappingKeys()
    {
        $this
            ->given($this->newTestedInstance)
            ->then
                ->array($this->testedInstance->getMappingKeys())
                    ->isEmpty()

            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->array($this->testedInstance->getMappingKeys())
                    ->isEqualTo(['orders', 'order_items', 'clients'])
        ;
    }

    /**
     * testGetKeyFromId
     */
    public function testGetKeyFromId()
    {
        $this
            // no mappings
            ->given($this->newTestedInstance)
            ->then($testedInstance = $this->testedInstance)
                ->exception(function () use ($testedInstance) {
                    $testedInstance->getKeyFromId('/orders/8');
                })
                ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                ->hasMessage('orders key is not mapped')

            // good instances
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->string($this->testedInstance->getKeyFromId('/orders/8'))
                    ->isEqualTo('orders')

            // a really complicated id
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->string($this->testedInstance->getKeyFromId('/sales/customers/3/orders/8'))
                    ->isEqualTo('orders')
        ;
    }

    /**
     * testPrefix
     */
    public function testPrefix()
    {
        $this
            // id prefix
            ->given($this->newTestedInstance('/v1'))
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->string($this->testedInstance->getKeyFromId('/v1/orders/8'))
                    ->isEqualTo('orders')
        ;
    }

    /**
     * testGetKeyFromModel
     */
    public function testGetKeyFromModel()
    {
        $this
            ->given($this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->string($this->testedInstance->getKeyFromModel('Foo\Bar\Model\OrderItem'))
                    ->isEqualTo('order_items')

            ->then($testedInstance = $this->testedInstance)
            ->exception(function () use ($testedInstance) {
                $testedInstance->getKeyFromModel('\Not\Viable\Classname');
            })
                ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                ->hasMessage('Model name \Not\Viable\Classname not found in mapping')
        ;
    }

    /**
     * testGetClassMetadata
     */
    public function testGetClassMetadata()
    {
        $this
            ->given($testedInstance = $this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->object($this->testedInstance->getClassMetadata('Foo\Bar\Model\Order'))
                    ->isInstanceOf('Mapado\RestClientSdk\Mapping\ClassMetadata')
                ->object($this->testedInstance->getClassMetadata('Foo\Bar\Model\Client'))
                    ->isInstanceOf('Mapado\RestClientSdk\Mapping\ClassMetadata')

            ->then
            ->exception(function () use ($testedInstance) {
                $testedInstance->getClassMetadata('Foo\Bar');
            })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\MappingException')
                    ->hasMessage('Foo\Bar model is not mapped')
        ;
    }

    /**
     * testHasClassMetadata
     */
    public function testHasClassMetadata()
    {
        $this
            ->given($testedInstance = $this->newTestedInstance)
                ->and($this->testedInstance->setMapping($this->getMappingArray()))
            ->then
                ->boolean($this->testedInstance->hasClassMetadata('Foo\Bar\Model\Order'))
                    ->isTrue()
                ->boolean($this->testedInstance->hasClassMetadata('Foo\Bar\Model\Client'))
                    ->isTrue()

            ->then
                ->boolean($testedInstance->hasClassMetadata('Foo\Bar'))
                    ->isFalse()
        ;
    }

    public function testMappingConfiguration()
    {
        $this
            // default configuration
            ->given($this->newTestedInstance())
            ->then
                ->array($this->testedInstance->getConfig())
                    ->isEqualTo([
                        'collectionKey' => 'hydra:member',
                    ])

            // custom configuration
            ->given($config = [
                'collectionKey' => 'collection',
            ])
                ->and($this->newTestedInstance('', $config))
            ->then
                ->array($this->testedInstance->getConfig())
                    ->isEqualTo($config)
        ;
    }

    /**
     * getMappingArray
     *
     * @return ClassMetadata[]
     */
    private function getMappingArray()
    {
        $order = new ClassMetadata(
            'orders',
            'Foo\Bar\Model\Order',
            'Foo\Bar\Client\OrderClient'
        );

        $orderItem = new ClassMetadata(
            'order_items',
            'Foo\Bar\Model\OrderItem',
            'Foo\Bar\Client\OrderItemClient'
        );

        $client = new ClassMetadata(
            'clients',
            'Foo\Bar\Model\Client',
            'Foo\Bar\Client\ClientClient'
        );

        return [$order, $orderItem, $client];
    }
}
