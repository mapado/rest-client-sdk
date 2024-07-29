<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Model;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mapado\RestClientSdk\EntityRepository;
use Mapado\RestClientSdk\Exception\MissingSetterException;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Driver\AttributeDriver;
use Mapado\RestClientSdk\Mapping\Relation;
use Mapado\RestClientSdk\Model\Serializer;
use Mapado\RestClientSdk\RestClient;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\Tests\Model\Issue46;
use Mapado\RestClientSdk\Tests\Model\Issue75;
use Mapado\RestClientSdk\Tests\Model\Issue80;
use Mapado\RestClientSdk\Tests\Model\Issue89;
use Mapado\RestClientSdk\Tests\Model\Issue90;
use Mapado\RestClientSdk\Tests\Model\JsonLd;
use Mapado\RestClientSdk\UnitOfWork;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Serializer
 */
class SerializerTest extends TestCase
{
    private UnitOfWork $unitOfWork;

    private Serializer $testedInstance;

    public function testJsonEncode(): void
    {
        $this->createNewInstance();

        $cart = $this->createCart();

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertSame(
            [
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [],
                'order' => null,
            ],
            $data
        );

        // reverse the serialization
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            $cart
        );
        $this->assertSame('/v1/carts/8', $cart->getId());
        $this->assertSame('payed', $cart->getStatus());
        $this->assertEquals(
            new \DateTime('2015-09-20T12:08:00'),
            $cart->getCreatedAt()
        );
        $this->assertEmpty($cart->getCartItemList());
    }

    public function testJsonEncodeRelationWithLink(): void
    {
        $this->createNewInstance();

        $cart = $this->createCart();
        $cartItem = $this->createKnownCartItem();
        $cart->addCartItemList($cartItem);

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ['serializeRelations' => ['cart_items']]
        );

        $this->assertSame(
            [
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        '@id' => '/v1/cart_items/16',
                        'amount' => 1,
                        'createdAt' => (new \DateTime(
                            '2015-11-04 15:13:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-11-04 15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'Jane',
                        ],
                        'cart' => '/v1/carts/8',
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );

        // reverse the serialization
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $this->assertCount(1, $cart->getCartItemList());
        $cartItem = current($cart->getCartItemList());

        $this->assertInstanceOf(JsonLd\CartItem::class, $cartItem);

        $this->assertSame('/v1/cart_items/16', $cartItem->getId());
    }

    public function testJsonEncodeRelationWithoutLink(): void
    {
        $this->createNewInstance();

        $cart = $this->createCart();
        $cartItem = $this->createNewCartItem();
        $cart->addCartItemList($cartItem);
        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $this->assertSame(
            [
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'amount' => 2,
                        'createdAt' => (new \DateTime(
                            '2015-09-20T12:11:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-09-20T15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'John',
                        ],
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );

        // reverse the serialization
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $cartItemList = $cart->getCartItemList(); // we can not uneserialize an unlinked entity

        $this->assertCount(1, $cartItemList);
        $this->assertInstanceOf(JsonLd\CartItem::class, $cartItemList[0]);

        $this->assertNull($cartItemList[0]->getId());
    }

    public function testSerializeThreeLevel(): void
    {
        $this->createNewInstance();

        $cart = $this->createNewCart();
        $cartItem = $this->createNewCartItem();
        $cart->addCartItemList($cartItem);
        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertSame(
            [
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'amount' => 2,
                        'createdAt' => (new \DateTime(
                            '2015-09-20T12:11:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-09-20T15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'John',
                        ],
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );
    }

    public function testJsonEncodeRelationWithoutLinkMultipleLevel(): void
    {
        $this->createNewInstance();
        $cart = $this->createCart();
        $cartItem = $this->createNewCartItem(false);

        $cartItem->addCartItemDetailList($this->createNewCartItemDetail());
        $cartItem->addCartItemDetailList($this->createNewCartItemDetail());
        $cart->addCartItemList($cartItem);

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $this->assertSame(
            [
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'amount' => 2,
                        'createdAt' => (new \DateTime(
                            '2015-09-20T12:11:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-09-20T15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'John',
                        ],
                        'cartItemDetailList' => [
                            ['name' => 'Bill'],
                            ['name' => 'Bill'],
                        ],
                    ],
                ],
                'order' => null,
            ],
            $data
        );
    }

    public function testJsonEncodeMixRelations(): void
    {
        $this->createNewInstance();

        $cart = $this->createCart();
        $cartItem = $this->createNewCartItem();
        $knownedCartItem = $this->createKnownCartItem();
        $cart->addCartItemList($knownedCartItem);
        $cart->addCartItemList($cartItem);

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ['serializeRelations' => ['cart_items']]
        );
        $this->assertSame(
            [
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        '@id' => '/v1/cart_items/16',
                        'amount' => 1,
                        'createdAt' => (new \DateTime(
                            '2015-11-04 15:13:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-11-04 15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'Jane',
                        ],
                        'cart' => '/v1/carts/8',
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                    [
                        'amount' => 2,
                        'createdAt' => (new \DateTime(
                            '2015-09-20T12:11:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-09-20T15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'John',
                        ],
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );

        // reverse the serialization
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $cartItemList = $cart->getCartItemList(); // we can not uneserialize an unlinked entity

        $this->assertCount(2, $cartItemList);
        $cartItem = $cartItemList[0];
        $this->assertInstanceOf(JsonLd\CartItem::class, $cartItem);
        $this->assertSame('/v1/cart_items/16', $cartItem->getId());
        $cartItem = $cartItemList[1];
        $this->assertInstanceOf(JsonLd\CartItem::class, $cartItem);
        $this->assertNull($cartItem->getId());
    }

    public function testNotAllowedSerialization(): void
    {
        $this->createNewInstance();

        $cartItem = $this->createNewCartItem();
        $cartItemDetail = $this->createNewCartItemDetail();
        $cartItemDetail->setCartItem($cartItem);
        $testedInstance = $this->testedInstance;

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            $cartItemDetail->getCartItem()
        );

        $this->expectException(
            \Mapado\RestClientSdk\Exception\SdkException::class
        );
        $this->expectExceptionMessage('Case not allowed for now');

        $testedInstance->serialize(
            $cartItemDetail,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail'
        );
    }

    public function testMultipleLevelSerialization(): void
    {
        $this->createNewInstance();
        $cart = $this->createNewCart();
        $cartItem = $this->createNewCartItem();
        $cartItem->setCart($cart);
        $this->assertSame(
            [
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'amount' => 2,
                        'createdAt' => (new \DateTime(
                            '2015-09-20T12:11:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-09-20T15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'John',
                        ],
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $this->testedInstance->serialize(
                $cart,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
            )
        );
    }

    public function testLinkedUnserialize(): void
    {
        $this->createNewInstance();
        $phoneNumberUtil = PhoneNumberUtil::getInstance();

        $data = [
            '@id' => '/v1/carts/8',
            'status' => 'payed',
            'clientPhoneNumber' => $phoneNumberUtil->parse(
                '+330123456789',
                PhoneNumberFormat::INTERNATIONAL
            ),
            'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                \DateTime::RFC3339
            ),
            'cart_items' => [
                [
                    '@id' => '/v1/cart_items/16',
                    'amount' => 2,
                    'createdAt' => (new \DateTime(
                        '2015-09-20T12:11:00+00:00'
                    ))->format(\DateTime::RFC3339),
                    'data' => [
                        'when' => (new \DateTime(
                            '2015-09-20T15:00:00+00:00'
                        ))->format(\DateTime::RFC3339),
                        'who' => 'John',
                    ],
                    'product' => '/v1/products/10',
                    'cartItemDetailList' => [],
                ],
            ],
        ];
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            $cart
        );

        $this->assertInstanceOf(
            'libphonenumber\PhoneNumber',
            $cart->getClientPhoneNumber()
        );

        $cartItemList = $cart->getCartItemList();

        $this->assertIsArray($cartItemList);
        $this->assertCount(1, $cartItemList);

        $cartItem = current($cart->getCartItemList());

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            $cartItem
        );

        $this->assertSame('/v1/cart_items/16', $cartItem->getId());
        $this->assertSame(2, $cartItem->getAmount());
        $this->assertEquals(
            new \DateTime('2015-09-20T12:11:00+00:00'),
            $cartItem->getCreatedAt()
        );
        $this->assertSame(
            [
                'when' => (new \DateTime('2015-09-20T15:00:00+00:00'))->format(
                    \DateTime::RFC3339
                ),
                'who' => 'John',
            ],
            $cartItem->getData()
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Product',
            $cartItem->getProduct()
        );

        $this->assertSame('/v1/products/10', $cartItem->getProduct()->getId());

        $this->assertIsArray($cartItem->getCartItemDetailList());
        $this->assertEmpty($cartItem->getCartItemDetailList());

        $this->createNewInstance();
        $data = [
            '@id' => '/v1/cart_items/16',
            'amount' => 2,
            'cart' => [
                '@id' => '/v1/carts/10',
                'status' => 'waiting',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
            ],
        ];

        $cartItem = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            $cartItem
        );
        $cart = $cartItem->getCart();

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            $cart
        );

        $this->assertSame('+33 1 23 45 67 89', $cart->getClientPhoneNumber());

        $this->assertSame('/v1/carts/10', $cart->getId());
        $this->assertSame('waiting', $cart->getStatus());
    }

    public function testSerializeNullValues(): void
    {
        $this->createNewInstance();
        $cart = $this->createNewCart();
        $cart->setStatus(null);
        $cart->setOrder(null);
        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );
        $this->assertSame(
            [
                'status' => null,
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [],
                'order' => null,
            ],
            $data
        );
    }

    public function testSerializingAttributeNameDiffThanPropertyName(): void
    {
        $this->createNewInstance();
        $product = $this->createNewProduct();
        $data = $this->testedInstance->serialize(
            $product,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Product'
        );
        $this->assertSame(
            [
                'product_value' => 8.2,
                'currency' => 'eur',
            ],
            $data
        );
    }

    public function testWeirdIdentifier(): void
    {
        $mapping = $this->getMapping('weirdId');
        $this->createNewInstance($mapping);

        $cart = $this->createCart();
        $cartItem = $this->createKnownCartItem();
        $cart->addCartItemList($cartItem);

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ['serializeRelations' => ['cart_items']]
        );
        $this->assertSame(
            [
                'weirdId' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'weirdId' => '/v1/cart_items/16',
                        'amount' => 1,
                        'createdAt' => (new \DateTime(
                            '2015-11-04 15:13:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-11-04 15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'Jane',
                        ],
                        'cart' => '/v1/carts/8',
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );

        $data = $this->testedInstance->serialize(
            $cart,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ['serializeRelations' => ['cart_items']]
        );
        $this->assertSame(
            [
                'weirdId' => '/v1/carts/8',
                'status' => 'payed',
                'clientPhoneNumber' => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                    \DateTime::RFC3339
                ),
                'cart_items' => [
                    [
                        'weirdId' => '/v1/cart_items/16',
                        'amount' => 1,
                        'createdAt' => (new \DateTime(
                            '2015-11-04 15:13:00'
                        ))->format(\DateTime::RFC3339),
                        'data' => [
                            'when' => (new \DateTime(
                                '2015-11-04 15:00:00'
                            ))->format(\DateTime::RFC3339),
                            'who' => 'Jane',
                        ],
                        'cart' => '/v1/carts/8',
                        'product' => '/v1/products/10',
                        'cartItemDetailList' => [],
                    ],
                ],
                'order' => null,
            ],
            $data
        );

        // reverse the serialization
        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            $cart
        );

        $this->assertSame('/v1/carts/8', $cart->getId());
        $this->assertIsArray($cart->getCartItemList());
        $this->assertCount(1, $cart->getCartItemList());

        $cartItem = current($cart->getCartItemList());

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            $cartItem
        );
        $this->assertSame('/v1/cart_items/16', $cartItem->getId());
    }

    public function testDeserializeWithExtraFields(): void
    {
        $this->createNewInstance();

        $data = [
            '@foo' => 'bar',
            '@id' => '/v1/carts/8',
            'status' => 'payed',
            'clientPhoneNumber' => '+33 1 23 45 67 89',
            'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(
                \DateTime::RFC3339
            ),
            'cart_items' => [],
            'order' => null,
        ];

        $cart = $this->testedInstance->deserialize(
            $data,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            $cart
        );
    }

    public function testSerializingIriManyToOne(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue46/')
        );

        $section = new Issue46\Section();
        $section->setId(46);
        $section->setIri('/sections/46');
        $section->setTitle('section title');

        $article = new Issue46\Article();
        $article->setSection($section);

        $this->createNewInstance($mapping);

        $this->assertSame(
            [
                'id' => null,
                'section' => '/sections/46',
            ],
            $this->testedInstance->serialize(
                $article,
                'Mapado\RestClientSdk\Tests\Model\Issue46\Article'
            )
        );

        $article->setIri('/articles/44');

        $this->assertSame(
            [
                '@id' => '/sections/46',
                'id' => 46,
                'title' => 'section title',
                'articleList' => ['/articles/44'],
            ],
            $this->testedInstance->serialize(
                $section,
                'Mapado\RestClientSdk\Tests\Model\Issue46\Section'
            )
        );

        $this->assertSame(
            [
                '@id' => '/sections/46',
                'id' => 46,
                'title' => 'section title',
                'articleList' => [
                    [
                        '@id' => '/articles/44',
                        'id' => null,
                        'section' => '/sections/46',
                    ],
                ],
            ],
            $this->testedInstance->serialize(
                $section,
                'Mapado\RestClientSdk\Tests\Model\Issue46\Section',
                ['serializeRelations' => ['articleList']]
            )
        );
    }

    public function testDeserializeEntityWithoutIriAttribute(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue75/')
        );

        $this->createNewInstance($mapping);
        $data = [
            '@id' => '/v1/articles/8',
            'tag' => [
                'name' => 'tag name',
            ],
        ];

        $article = $this->testedInstance->deserialize(
            $data,
            Issue75\Article::class
        );

        $this->assertInstanceOf(Issue75\Article::class, $article);
        $this->assertInstanceOf(Issue75\Tag::class, $article->getTag());

        $data = [
            '@id' => '/v1/articles/8',
            'tagList' => [
                [
                    'name' => 'tag 1 name',
                ],
                [
                    'name' => 'tag 2 name',
                ],
            ],
        ];

        $article = $this->testedInstance->deserialize(
            $data,
            Issue75\Article::class
        );

        $this->assertInstanceOf(Issue75\Article::class, $article);
        $tagList = $article->getTagList();
        $this->assertIsArray($tagList);
        $this->assertCount(2, $tagList);
        $this->assertInstanceOf(Issue75\Tag::class, $tagList[0]);
        $this->assertSame('tag 1 name', $tagList[0]->getName());

        $this->assertInstanceOf(Issue75\Tag::class, $tagList[1]);
        $this->assertSame('tag 2 name', $tagList[1]->getName());
    }

    public function testSerializeEntityWithoutIriAttribute(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue75/')
        );

        $tag = new Issue75\Tag();
        $tag->setName('tag title');

        $article = new Issue75\Article();
        $article->setTitle('article title');

        $this->createNewInstance($mapping);
        $data = $this->testedInstance->serialize(
            $article,
            Issue75\Article::class
        );
        $this->assertSame(
            [
                'title' => 'article title',
                'tag' => null,
                'tagList' => null,
            ],
            $data
        );

        $article->setTag($tag);
        $data = $this->testedInstance->serialize(
            $article,
            Issue75\Article::class
        );
        $this->assertsame(
            [
                'title' => 'article title',
                'tag' => [
                    'name' => 'tag title',
                ],
                'tagList' => null,
            ],
            $data
        );

        $article->setTagList([(new Issue75\Tag())->setName('tag 1')]);
        $data = $this->testedInstance->serialize(
            $article,
            Issue75\Article::class
        );
        $this->assertSame(
            [
                'title' => 'article title',
                'tag' => [
                    'name' => 'tag title',
                ],
                'tagList' => [['name' => 'tag 1']],
            ],
            $data
        );

        // as tags does not have an Attribute identifier, we ignore the serializeRelations context
        $data = $this->testedInstance->serialize(
            $article,
            Issue75\Article::class,
            ['serializeRelations' => ['tag', 'tagList']]
        );
        $this->assertSame(
            [
                'title' => 'article title',
                'tag' => [
                    'name' => 'tag title',
                ],
                'tagList' => [['name' => 'tag 1']],
            ],
            $data
        );
    }

    public function testDeserializeEntityWithIntAsId(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue90/')
        );

        $this->createNewInstance($mapping);
        $data = [
            'id' => 8,
        ];

        $article = $this->testedInstance->deserialize(
            $data,
            Issue90\WithIdInt::class
        );

        $this->assertInstanceOf(Issue90\WithIdInt::class, $article);

        $this->assertIsObject($this->unitOfWork->getDirtyEntity('8'));
    }

    public function testDeserializeEntityWithAnInexistantSetter(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue80/')
        );

        $this->createNewInstance($mapping);
        $data = [
            'id' => 8,
            'title' => 'some title',
        ];

        $this->expectException(MissingSetterException::class);
        $this->expectExceptionMessage(
            'Property title is not writable for class Mapado\RestClientSdk\Tests\Model\Issue80\Article. Please make it writable. You can check the property-access documentation here : https://symfony.com/doc/current/components/property_access.html#writing-to-objects'
        );

        $this->testedInstance->deserialize($data, Issue80\Article::class);
    }

    public function testDeserializeEntityWithPublicProperty(): void
    {
        $AttributeDriver = new AttributeDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping(
            $AttributeDriver->loadDirectory(__DIR__ . '/../../Model/Issue89/')
        );

        $this->createNewInstance($mapping);
        $data = [
            'id' => 8,
            'title' => 'some title',
            'tagList' => ['/tags/2'],
        ];

        $article = $this->testedInstance->deserialize(
            $data,
            Issue89\Article::class
        );

        $this->assertInstanceOf(Issue89\Article::class, $article);

        $this->assertSame(8, $article->id);

        $this->assertSame('some title', $article->title);
    }

    private function getMapping($idKey = '@id'): Mapping
    {
        $mapping = new Mapping('/v1');
        $mapping->setMapping([
            $this->getCartMetadata($idKey),
            $this->getCartItemMetadata($idKey),
            $this->getCartItemDetailMetadata($idKey),
            $this->getProductMetadata($idKey),
        ]);

        return $mapping;
    }

    private function getProductMetadata(string $idKey): ClassMetadata
    {
        $productMetadata = new ClassMetadata(
            'products',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Product',
            ''
        );

        $productMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('product_value', 'value'),
            new Attribute('currency'),
        ]);

        return $productMetadata;
    }

    private function getCartItemDetailMetadata(string $idKey): ClassMetadata
    {
        $cartItemDetailMetadata = new ClassMetadata(
            'cart_item_details',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail',
            ''
        );

        $cartItemDetailMetadata->setRelationList([
            new Relation(
                'cartItem',
                Relation::MANY_TO_ONE,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
            ),
        ]);
        $cartItemDetailMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('name'),
            new Attribute('cartItem'),
        ]);

        return $cartItemDetailMetadata;
    }

    private function getCartItemMetadata(string $idKey): ClassMetadata
    {
        $cartItemMetadata = new ClassMetadata(
            'cart_items',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            ''
        );

        $cartItemMetadata->setRelationList([
            new Relation(
                'cart',
                Relation::MANY_TO_ONE,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
            ),
            new Relation(
                'product',
                Relation::MANY_TO_ONE,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Product'
            ),
            new Relation(
                'cartItemDetailList',
                Relation::ONE_TO_MANY,
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail'
            ),
        ]);
        $cartItemMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('amount'),
            new Attribute('createdAt', 'createdAt', 'datetime'),
            new Attribute('data'),
            new Attribute('cart'),
            new Attribute('product'),
            new Attribute('cartItemDetailList'),
        ]);

        return $cartItemMetadata;
    }

    private function getCartMetadata(string $idKey): ClassMetadata
    {
        $cartMetadata = new ClassMetadata(
            'carts',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            ''
        );
        $cartMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
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
                'cart_items',
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

    private function createNewCart(): \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart
    {
        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setStatus('payed');
        $cart->setCreatedAt(new \DateTime('2015-09-20 12:08:00'));

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $clientPhoneNumber = $phoneNumberUtil->parse(
            '+33123456789',
            PhoneNumberFormat::INTERNATIONAL
        );
        $cart->setClientPhoneNumber($clientPhoneNumber);

        return $cart;
    }

    private function createCart(): \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart
    {
        $cart = $this->createNewCart();
        $cart->setId('/v1/carts/8');

        return $cart;
    }

    private function createKnownCartItem(): \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem
    {
        $cartItem = $this->createNewCartItem();
        $cartItem->setId('/v1/cart_items/16');
        $cartItem->setAmount(1);
        $cartItem->setCreatedAt(new \DateTimeImmutable('2015-11-04 15:13:00'));
        $cartItem->setData([
            'when' => new \DateTimeImmutable('2015-11-04 15:00:00'),
            'who' => 'Jane',
        ]);
        $cartItem->setCart($this->createCart());

        return $cartItem;
    }

    private function createNewCartItem(
        $addKnownedProduct = true
    ): \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem {
        $cartItem = new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem();
        $cartItem->setAmount(2);
        $cartItem->setCreatedAt(new \DateTimeImmutable('2015-09-20 12:11:00'));
        $cartItem->setData([
            'when' => new \DateTime('2015-09-20 15:00:00'),
            'who' => 'John',
        ]);

        if ($addKnownedProduct) {
            $cartItem->setProduct($this->createKnownedProduct());
        }

        return $cartItem;
    }

    private function createNewProduct(): \Mapado\RestClientSdk\Tests\Model\JsonLd\Product
    {
        $product = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Product();

        $product->setValue(8.2);
        $product->setCurrency('eur');

        return $product;
    }

    private function createKnownedProduct(): \Mapado\RestClientSdk\Tests\Model\JsonLd\Product
    {
        $product = $this->createNewProduct();
        $product->setId('/v1/products/10');

        return $product;
    }

    private function createNewCartItemDetail(): \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail
    {
        $item = new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItemDetail();

        $item->setName('Bill');

        return $item;
    }

    private function createNewInstance(Mapping $mapping = null): void
    {
        $mapping = $mapping ?: $this->getMapping();
        $this->unitOfWork = new UnitOfWork($mapping);
        $this->testedInstance = new Serializer($mapping, $this->unitOfWork);

        $restClient = $this->createMock(
            RestClient::class
        );
        $sdk = $this->getMockBuilder(SdkClient::class)
            ->setConstructorArgs([
                $restClient,
                $mapping,
                $this->unitOfWork,
                $this->testedInstance,
            ])
            ->onlyMethods(['getRepository'])
            ->getMock();
        $sdk->setFileCachePath(__DIR__ . '/../../cache/');

        $cartRepositoryMock = $this->getCartRepositoryMock(
            $sdk,
            $restClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $sdk->method('getRepository')->willReturnCallback(function (
            $modelName
        ) use ($cartRepositoryMock) {
            switch ($modelName) {
                case 'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart':
                    return $cartRepositoryMock;

                default:
                    return;
            }
        });

        $this->testedInstance->setSdk($sdk);
    }

    private function getCartRepositoryMock(
        SdkClient $sdk,
        RestClient $restClient,
        UnitOfWork $unitOfWork,
        string $modelName
    ): EntityRepository {
        $repository = $this->getMockBuilder(
            EntityRepository::class
        )
            ->setConstructorArgs([$sdk, $restClient, $unitOfWork, $modelName])
            ->getMock();

        $repository
            ->method('find')
            ->willReturnCallback(fn() => $this->createCart());

        return $repository;
    }
}
