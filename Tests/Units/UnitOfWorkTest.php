<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;
use Mapado\RestClientSdk\UnitOfWork;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers UnitOfWork
 */
class UnitOfWorkTest extends TestCase
{
    public function testRegister(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $ticket = (object) [
            'firstname' => 'foo',
            'lastname' => 'bar',
            'email' => 'foo.bar@gmail.com',
        ];
        $unitOfWork->registerClean('@id1', $ticket);

        $this->assertEquals($ticket, $unitOfWork->getDirtyEntity('@id1'));
    }

    #[DataProvider('simpleEntityDataProvider')]
    public function testSimpleEntity(
        array $new,
        array $old,
        array $expected
    ): void {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $this->assertSame(
            $expected,
            $unitOfWork->getDirtyData($new, $old, $this->getCartMetadata())
        );
    }

    /**
     * @return iterable<array{new: array, old: array, expected: array}>
     */
    public static function simpleEntityDataProvider(): iterable
    {
        yield [
            'new' => [
                '@id' => '/v12/carts/1',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
            ],
            'expected' => [],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'order' => '/v12/orders/1',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'order' => '/v12/orders/1',
            ],
            'expected' => [],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'waiting',
            ],
            'expected' => [
                'status' => 'payed',
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/2',
                'status' => 'payed',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'waiting',
            ],
            'expected' => [
                '@id' => '/v12/carts/2',
                'status' => 'payed',
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'someData' => [
                    'foo' => 'bar',
                    'loo' => 'baz',
                ],
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'someData' => [
                    'foo' => 'bar',
                ],
            ],
            'expected' => [
                'someData' => [
                    'foo' => 'bar',
                    'loo' => 'baz',
                ],
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'someData' => [
                    'foo' => 'bar',
                    'bad' => 'baz',
                ],
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'payed',
                'someData' => [
                    'foo' => 'bar',
                    'bad' => 'baz',
                ],
            ],
            'expected' => [],
        ];
    }

    public function testWithMoreData(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $this->assertSame(
            [],
            $unitOfWork->getDirtyData(
                [
                    '@id' => '/v12/carts/1',
                ],
                [
                    '@id' => '/v12/carts/1',
                    'foo' => 'bar',
                    'status' => 'ok',
                ],
                $this->getCartMetadata()
            )
        );
    }

    #[DataProvider('manyToOneRelationDataProvider')]
    public function testManyToOneRelation(
        array $new,
        array $old,
        array $expected
    ): void {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);
        $this->assertSame(
            $expected,
            $unitOfWork->getDirtyData($new, $old, $this->getCartMetadata())
        );
    }

    public static function manyToOneRelationDataProvider(): iterable
    {
        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'order' => '/v1/orders/2',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'order' => ['@id' => '/v1/orders/1'],
            ],
            'expected' => [
                'order' => '/v1/orders/2',
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'order' => [
                    '@id' => '/v1/orders/2',
                ],
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'order' => '/v1/orders/1',
            ],
            'expected' => [
                'order' => [
                    '@id' => '/v1/orders/2',
                ],
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'order' => [
                    '@id' => '/v1/orders/2',
                    'status' => 'payed',
                ],
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'order' => [
                    '@id' => '/v1/orders/1',
                    'status' => 'payed',
                ],
            ],
            'expected' => [
                'order' => [
                    '@id' => '/v1/orders/2',
                ],
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'order' => [
                    '@id' => '/v1/orders/2',
                    'status' => 'payed',
                ],
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'order' => [
                    '@id' => '/v1/orders/1',
                    'status' => 'waiting',
                ],
            ],
            'expected' => [
                'order' => [
                    '@id' => '/v1/orders/2',
                    'status' => 'payed',
                ],
            ],
        ];
    }

    public function testNoChanges(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $newSerializedModel = [
            '@id' => '/v12/carts/1',
            'cartItemList' => [
                '/v12/cart_items/1',
                '/v12/cart_items/2',
                '/v12/cart_items/3',
            ],
        ];
        $this->assertSame(
            [],
            $unitOfWork->getDirtyData(
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
            )
        );

        $this->assertSame(
            [],
            $unitOfWork->getDirtyData(
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
            )
        );
    }

    public function testNoMetadata(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $newSerializedModel = [
            '@id' => '/v12/carts/1',
            'cartInfo' => [
                [
                    'firstname' => 'john',
                    'lastname' => 'doe',
                ],
            ],
        ];

        $this->assertSame(
            [],
            $unitOfWork->getDirtyData(
                $newSerializedModel,
                [
                    '@id' => '/v12/carts/1',
                    'cartInfo' => [
                        [
                            'firstname' => 'john',
                            'lastname' => 'doe',
                        ],
                    ],
                ],
                $this->getCartMetadata()
            )
        );

        $this->assertSame(
            [],
            $unitOfWork->getDirtyData(
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
            )
        );
    }

    public function testRemoveItem(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $this->assertSame(
            [
                'cartItemList' => ['/v12/cart_items/1', '/v12/cart_items/2'],
            ],
            $unitOfWork->getDirtyData(
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
            )
        );

        $this->assertSame(
            [
                'cartItemList' => ['/v12/cart_items/1', '/v12/cart_items/3'],
            ],
            $unitOfWork->getDirtyData(
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
            )
        );

        $this->assertSame(
            [
                'cartItemList' => [
                    [
                        '@id' => '/v12/cart_items/1',
                    ],
                ],
            ],
            $unitOfWork->getDirtyData(
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
            )
        );
        $this->assertSame(
            [
                'cartItemList' => [
                    [
                        '@id' => '/v12/cart_items/2',
                    ],
                ],
            ],
            $unitOfWork->getDirtyData(
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
            )
        );

        $this->assertSame(
            [
                'cartItemList' => [
                    [
                        'amount' => 2,
                        '@id' => '/v12/cart_items/2',
                    ],
                ],
            ],
            $unitOfWork->getDirtyData(
                [
                    '@id' => '/v12/carts/1',
                    'cartItemList' => [
                        [
                            '@id' => '/v12/cart_items/2',
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
            )
        );
    }

    public function testAddItem(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $this->assertSame(
            [
                'cartItemList' => [
                    '/v12/cart_items/1',
                    '/v12/cart_items/2',
                    '/v12/cart_items/3',
                ],
            ],
            $unitOfWork->getDirtyData(
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
            )
        );

        $this->assertSame(
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
            ],
            $unitOfWork->getDirtyData(
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
            )
        );
    }

    #[DataProvider('sendOnlyDirtyDataProvider')]
    public function testSendOnlyDirty(
        array $new,
        array $old,
        array $expected
    ): void {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $this->assertSame(
            $expected,
            $unitOfWork->getDirtyData($new, $old, $this->getCartMetadata())
        );
    }

    public static function sendOnlyDirtyDataProvider(): iterable
    {
        yield [
            'new' => [
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
            'old' => [
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
            'expected' => [
                'cartItemList' => [
                    [
                        'amount' => 2,
                        '@id' => '/v12/cart_items/1',
                    ],
                ],
            ],
        ];

        yield [
            'new' => [
                '@id' => '/v12/carts/1',
                'status' => 'ok',
                'clientPhoneNumber' => '+33123456789',
            ],
            'old' => [
                '@id' => '/v12/carts/1',
                'status' => 'ko',
                'clientPhoneNumber' => '+33123456789',
            ],
            'expected' => [
                'status' => 'ok',
            ],
        ];

        yield [
            'new' => [
                'cartItemList' => [
                    [
                        '@id' => '/v12/cart_items/1',
                        'amount' => 1,
                        'cartItemDetailList' => [
                            [
                                '@id' => '/v12/cart_item_details/1',
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
                                '@id' => '/v12/cart_item_details/2',
                                'name' => '',
                            ],
                        ],
                    ],
                ],
            ],
            'old' => [
                'cartItemList' => [
                    [
                        '@id' => '/v12/cart_items/1',
                        'amount' => 2,
                        'cartItemDetailList' => [
                            '@id' => '/v12/cart_item_details/1',
                            'name' => 'foo',
                        ],
                    ],
                    [
                        '@id' => '/v12/cart_items/3',
                        'amount' => 1,
                    ],
                ],
            ],

            'expected' => [
                'cartItemList' => [
                    [
                        'amount' => 1,
                        'cartItemDetailList' => [
                            [
                                '@id' => '/v12/cart_item_details/1',
                                'name' => '',
                            ],
                        ],
                        '@id' => '/v12/cart_items/1',
                    ],
                    [
                        '@id' => '/v12/cart_items/2',
                        'amount' => 1,
                    ],
                    [
                        'cartItemDetailList' => [
                            [
                                '@id' => '/v12/cart_item_details/2',
                                'name' => '',
                            ],
                        ],
                        '@id' => '/v12/cart_items/3',
                    ],
                ],
            ],
        ];
    }

    public function testNoMetadataChangeArray(): void
    {
        $mapping = $this->getMapping();
        $unitOfWork = new UnitOfWork($mapping);

        $newSerializedModel = [
            '@id' => '/v12/carts/1',
            'cartInfo' => [
                [
                    'firstname' => 'jane',
                    'lastname' => 'doe',
                    'children' => ['rusty', 'john-john-junior'],
                ],
            ],
        ];

        $this->assertSame(
            [
                'cartInfo' => [
                    [
                        'firstname' => 'jane',
                        'lastname' => 'doe',
                        'children' => ['rusty', 'john-john-junior'],
                    ],
                ],
            ],
            $unitOfWork->getDirtyData(
                $newSerializedModel,
                [
                    '@id' => '/v12/carts/1',
                    'cartInfo' => [
                        [
                            'firstname' => 'john',
                            'lastname' => 'doe',
                            'children' => ['rusty', 'john-john-junior'],
                        ],
                    ],
                ],
                $this->getCartMetadata()
            )
        );

        $this->assertSame(
            [
                'order' => [
                    'customerPaidAmount' => 1500,
                    '@id' => '/v12/orders/1',
                ],
            ],
            $unitOfWork->getDirtyData(
                [
                    '@id' => '/v12/carts/1',
                    'order' => [
                        '@id' => '/v12/orders/1',
                        'customerPaidAmount' => 1500,
                        'status' => 'awaiting_payment',
                    ],
                ],
                [
                    '@id' => '/v12/carts/1',
                    'order' => [
                        '@id' => '/v12/orders/1',
                        'customerPaidAmount' => 1000,
                        'status' => 'awaiting_payment',
                    ],
                ],
                $this->getCartMetadata()
            )
        );
    }

    private function getMapping(): Mapping
    {
        $mapping = $mapping = new Mapping();
        $mapping->setMapping([
            $this->getOrderMetadata(),
            $this->getCartMetadata(),
            $this->getCartItemMetadata(),
            $this->getCartItemDetailMetadata(),
        ]);

        return $mapping;
    }

    private function getOrderMetadata(): ClassMetadata
    {
        $orderMetadata = new ClassMetadata(
            'orders',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order',
            ''
        );

        $orderMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('customerPaidAmount', 'customerPaidAmount', 'int'),
            new Attribute('status'),
        ]);

        $orderMetadata->setRelationList([]);

        return $orderMetadata;
    }

    private function getCartMetadata(): ClassMetadata
    {
        $cartMetadata = new ClassMetadata(
            'carts',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ''
        );

        $cartMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('status'),
            new Attribute(
                'clientPhoneNumber',
                'clientPhoneNumber',
                'phone_number'
            ),
            new Attribute('createdAt', 'createdAt', 'datetime'),
            new Attribute('cart_items', 'cartItemList'),
            new Attribute('order'),
        ]);

        $cartMetadata->setRelationList([
            new Relation(
                'cartItemList',
                Relation::ONE_TO_MANY,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
            ),
            new Relation(
                'order',
                Relation::MANY_TO_ONE,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Order'
            ),
        ]);

        return $cartMetadata;
    }

    private function getCartItemMetadata(): ClassMetadata
    {
        $cartItemMetadata = new ClassMetadata(
            'cart_items',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            ''
        );

        $cartItemMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
            new Attribute('amount'),
            new Attribute('cartItemDetailList'),
        ]);

        $cartItemMetadata->setRelationList([
            new Relation(
                'cartItemDetailList',
                Relation::ONE_TO_MANY,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail'
            ),
        ]);

        return $cartItemMetadata;
    }

    private function getCartItemDetailMetadata(): ClassMetadata
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
