<?php

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * UnitOfWork
 *
 * @uses atoum
 * @author Julien Petit <julien.petit@mapado.com>
 */
class UnitOfWork extends atoum
{
    private $unitOfWork;

    public function testRegister()
    {
        $mapping = $this->getMapping();
        $unitOfWork = $this->newTestedInstance($mapping);

        $ticket = (object) [
            'firstname' => 'foo',
            'lastname' => 'bar',
            'email' => 'foo.bar@gmail.com',
        ];
        $unitOfWork->registerClean('@id1', $ticket);
        $this
            ->then
                ->variable($unitOfWork->getDirtyEntity('@id1'))
                    ->isEqualTo($ticket)
        ;
    }

    public function testNoChanges()
    {
        $mapping = $this->getMapping();
        $unitOfWork = $this->newTestedInstance($mapping);

        $this
            ->given($newSerializedModel = [
                '@id' => '/v12/carts/1',
                'cartItemList' => [
                    '/v12/cart_items/1',
                    '/v12/cart_items/2',
                    '/v12/cart_items/3',
                ],
            ])
            ->then
                ->variable($unitOfWork->getDirtyData(
                    $newSerializedModel,
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                            '/v12/cart_items/3',
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                    ]
                )
        ;
    }

    public function testRemoveItem()
    {
        $mapping = $this->getMapping();
        $unitOfWork = $this->newTestedInstance($mapping);

        $this
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                            '/v12/cart_items/3',
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                        ],
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/3',
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                            '/v12/cart_items/3',
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/3',
                        ],
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                            ],
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                            ],
                        ],
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ]
                )
        ;
    }

    public function testAddItem()
    {
        $mapping = $this->getMapping();
        $unitOfWork = $this->newTestedInstance($mapping);

        $this
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                            '/v12/cart_items/3',
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            '/v12/cart_items/1',
                            '/v12/cart_items/2',
                            '/v12/cart_items/3',
                        ],
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ]
                )
        ;
    }

    public function testSendOnlyDirty()
    {
        $mapping = $this->getMapping();
        $unitOfWork = $this->newTestedInstance($mapping);

        $this
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                            ],
                        ],
                    ]
                )
             ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        '@id' => '/v12/carts/1',
                        'status' => 'ok',
                        'clientPhoneNumber' => '+33123456789',
                    ],
                    [
                        '@id' => '/v12/carts/1',
                        'status' => 'ko',
                        'clientPhoneNumber' => '+33123456789',
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'status' => 'ok',
                    ]
                )
            ->then
                ->variable($unitOfWork->getDirtyData(
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 1,
                                'cartItemDetailList' => [
                                    [
                                        '@id' => '/v12/cart_item_detail/1',
                                        'name' => '',
                                    ],
                                ],
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                                'amount' => 1,
                            ],
                            [
                                '@id' => '/v12/cart_items/3',
                                'amount' => 1,
                                'cartItemDetailList' => [
                                    [
                                        '@id' => '/v12/cart_item_detail/2',
                                        'name' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'cartItemList' => [
                            [
                                '@id' => '/v12/cart_items/1',
                                'amount' => 2,
                                'cartItemDetailList' => [
                                    '@id' => '/v12/cart_item_detail/1',
                                    'name' => 'foo',
                                ],
                            ],
                            [
                                '@id' => '/v12/cart_items/3',
                                'amount' => 1,
                            ],
                        ],
                    ],
                    $this->getCartMetadata()
                ))
                ->isEqualTo(
                    [
                        'cartItemList' => [
                            [
                                'amount' => 1,
                                'cartItemDetailList' => [
                                    [
                                        '@id' => '/v12/cart_item_detail/1',
                                        'name' => '',
                                    ],
                                ],
                                '@id' => '/v12/cart_items/1',
                            ],
                            [
                                '@id' => '/v12/cart_items/2',
                            ],
                            [
                                '@id' => '/v12/cart_items/3',
                                'amount' => 1,
                                'cartItemDetailList' => [
                                    [
                                        '@id' => '/v12/cart_item_detail/2',
                                        'name' => '',
                                    ]
                                ],
                            ],
                        ]
                    ]
                )
        ;
    }

    private function getMapping()
    {
        $mapping = $mapping = new RestMapping();
        $mapping->setMapping([
            $this->getCartMetadata(),
            $this->getCartItemMetadata(),
            $this->getCartItemDetailMetadata(),
        ]);

        return $mapping;
    }

    private function getCartMetadata()
    {
        $cartMetadata = new ClassMetadata(
            'carts',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ''
        );

        $cartMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('status'),
            new Attribute('clientPhoneNumber', 'clientPhoneNumber', 'phone_number'),
            new Attribute('createdAt', 'createdAt', 'datetime'),
            new Attribute('cart_items', 'cartItemList'),
            new Attribute('order'),
        ]);

        $cartMetadata->setRelationList([
            new Relation('cartItemList', Relation::ONE_TO_MANY, 'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'),
        ]);

        return $cartMetadata;
    }

    private function getCartItemMetadata()
    {
        $cartItemMetadata = new ClassMetadata(
            'cart_items',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            ''
        );

        $cartItemMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('amount'),
            new Attribute('cart_item_details', 'cartItemDetailList'),
        ]);

        $cartItemMetadata->setRelationList([
            new Relation('cartItemDetailList', Relation::ONE_TO_MANY, 'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail'),
        ]);

        return $cartItemMetadata;
    }

    private function getCartItemDetailMetadata()
    {
        $cartItemDetailMetadata = new ClassMetadata(
            'cart_item_details',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail',
            ''
        );

        $cartItemDetailMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('name'),
        ]);

        return $cartItemDetailMetadata;
    }
}
