<?php

namespace Mapado\RestClientSdk\Tests\Units\Model;

use atoum;
use DateTime;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver;
use Mapado\RestClientSdk\Mapping\Relation;
use Mapado\RestClientSdk\Tests\Model\Issue46;

/**
 * Class Serializer
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Serializer extends atoum
{
    /**
     * testJsonEncode
     *
     * @access public
     * @return void
     */
    public function testJsonEncode()
    {
        $this->createNewInstance();

        $this
            ->given($cart = $this->createCart())
            ->then
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        "clientPhoneNumber" => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [],
                        'order' => null,
                    ])

            // reverse the serialization
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Cart')
                ->string($cart->getId())
                    ->isEqualTo('/v1/carts/8')
                ->string($cart->getStatus())
                    ->isEqualTo('payed')
                ->datetime($cart->getCreatedAt())
                    ->isEqualTo(new \DateTime('2015-09-20T12:08:00'))
                ->array($cart->getCartItemList())
                    ->isEmpty()

        ;
    }

    /**
     * testJsonEncodeRelation
     *
     * @access public
     * @return void
     */
    public function testJsonEncodeRelationWithLink()
    {
        $this->createNewInstance();

        $this
            ->given($cart = $this->createCart())
                ->and($cartItem = $this->createKnownCartItem())
                ->and($cart->addCartItemList($cartItem))

            ->then
                ->array($data = $this->testedInstance->serialize(
                    $cart,
                    'Mapado\RestClientSdk\Tests\Model\Cart',
                    [ 'serializeRelations' => ['cart_items'] ]
                ))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                '@id' => '/v1/cart_items/16',
                                'amount' => 1,
                                'createdAt' => (new \DateTime('2015-11-04 15:13:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-11-04 15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'Jane',
                                ],
                                'cart' => '/v1/carts/8',
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            ->then
                ->array($data = $this->testedInstance->serialize(
                    $cart,
                    'Mapado\RestClientSdk\Tests\Model\Cart',
                    [ 'serializeRelations' => ['cart_items'] ]
                ))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                '@id' => '/v1/cart_items/16',
                                'amount' => 1,
                                'createdAt' => (new \DateTime('2015-11-04 15:13:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-11-04 15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'Jane',
                                ],
                                'cart' => '/v1/carts/8',
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            // reverse the serialization
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                ->array($cart->getCartItemList())
                    ->size->isEqualTo(1)
                ->object($cartItem = current($cart->getCartItemList()))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                ->string($cartItem->getId())
                    ->isEqualTo('/v1/cart_items/16')
            ;
    }

    /**
     * testJsonEncodeRelationWithoutLink
     *
     * @access public
     * @return void
     */
    public function testJsonEncodeRelationWithoutLink()
    {
        $this->createNewInstance();

        $this
            ->given($cart = $this->createCart())
                ->and($cartItem = $this->createNewCartItem())
                ->and($cart->addCartItemList($cartItem))
            ->then
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            // reverse the serialization
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                ->array($cart->getCartItemList()) // we can not uneserialize an unlinked entity
                    ->isEmpty()
        ;
    }

    public function testSerializeThreeLevel()
    {
        $this->createNewInstance();

        $this
            ->given($cart = $this->createNewCart())
                ->and($cartItem = $this->createNewCartItem())
                ->and($cart->addCartItemList($cartItem))
            ->then
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])
        ;
    }

    /**
     * testJsonEncodeRelationWithoutLinkMultipleLevel
     *
     * @access public
     * @return void
     */
    public function testJsonEncodeRelationWithoutLinkMultipleLevel()
    {
        $this->createNewInstance();
        $this
            ->given($cart = $this->createCart())
                ->and($cartItem = $this->createNewCartItem(false))
                ->and($cartItem->addCartItemDetailList($this->createNewCartItemDetail()))
                ->and($cartItem->addCartItemDetailList($this->createNewCartItemDetail()))
            ->if($cart->addCartItemList($cartItem))
            ->then
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'cartItemDetailList' => [
                                    [ 'name' => 'Bill' ],
                                    [ 'name' => 'Bill', ],
                                ],
                            ],
                        ],
                        'order' => null,
                    ])
        ;
    }

    /**
     * testJsonEncodeMixRelations
     *
     * @access public
     * @return void
     */
    public function testJsonEncodeMixRelations()
    {
        $this->createNewInstance();

        $this
            ->given($cart = $this->createCart())
                ->and($cartItem = $this->createNewCartItem())
                ->and($knownedCartItem = $this->createKnownCartItem())
            ->if($cart->addCartItemList($knownedCartItem))
                ->and($cart->addCartItemList($cartItem))
            ->then
                ->array($data = $this->testedInstance->serialize(
                    $cart,
                    'Mapado\RestClientSdk\Tests\Model\Cart',
                    [ 'serializeRelations' => ['cart_items'] ]
                ))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                '@id' => '/v1/cart_items/16',
                                'amount' => 1,
                                'createdAt' => (new \DateTime('2015-11-04 15:13:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-11-04 15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'Jane',
                                ],
                                'cart' => '/v1/carts/8',
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            // reverse the serialization
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                ->array($cart->getCartItemList()) // we can not uneserialize an unlinked entity
                    ->size->isEqualTo(1)
                ->object($cartItem = current($cart->getCartItemList()))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                ->string($cartItem->getId())
                    ->isEqualTo('/v1/cart_items/16')
        ;
    }

    /**
     * testNotAllowedSerialization
     *
     * @access public
     * @return void
     */
    public function testNotAllowedSerialization()
    {
        $this->createNewInstance();
        $this
            ->given($cartItem = $this->createNewCartItem())
                ->and($cartItemDetail = $this->createNewCartItemDetail())
                ->and($cartItemDetail->setCartItem($cartItem))
                ->and($testedInstance = $this->testedInstance)
            ->then
                ->object($cartItemDetail->getCartItem())
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                ->exception(function () use ($testedInstance, $cartItemDetail) {
                    $testedInstance->serialize($cartItemDetail, 'Mapado\RestClientSdk\Tests\Model\CartItemDetail');
                })
                    ->isInstanceOf('Mapado\RestClientSdk\Exception\SdkException')
        ;
    }

    /**
     * testMultipleLevelSerialization
     *
     * @access public
     * @return void
     */
    public function testMultipleLevelSerialization()
    {
        $this->createNewInstance();
        $this
            ->given($cart = $this->createNewCart())
                ->and($cartItem = $this->createNewCartItem())
                ->and($cartItem->setCart($cart))
            ->then
                ->array($this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

        ;
    }

    /**
     * testLinkedUnserialize
     *
     * @access public
     * @return void
     */
    public function testLinkedUnserialize()
    {
        $this->createNewInstance();
        $phoneNumberUtil = PhoneNumberUtil::getInstance();

        $this
            ->given($data = [
                    '@id' => '/v1/carts/8',
                    'status' => 'payed',
                    'clientPhoneNumber' => $phoneNumberUtil->parse('+330123456789', PhoneNumberFormat::INTERNATIONAL),
                    'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                    'cart_items' => [
                        [
                            '@id' => '/v1/cart_items/16',
                            'amount' => 2,
                            'createdAt' => (new \DateTime('2015-09-20T12:11:00+00:00'))->format(DateTime::RFC3339),
                            'data' => [
                                'when' => (new \DateTime('2015-09-20T15:00:00+00:00'))->format(DateTime::RFC3339),
                                'who' => 'John',
                            ],
                            'product' => '/v1/products/10',
                            'cartItemDetailList' => [],
                        ],
                    ],
                ])
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Cart')
                ->object($cart->getClientPhoneNumber())
                    ->isInstanceOf('libphonenumber\PhoneNumber')
                ->array($cart->getCartItemList())
                    ->size->isEqualTo(1)
                ->object($cartItem = current($cart->getCartItemList()))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                ->string($cartItem->getId())
                    ->isEqualTo('/v1/cart_items/16')
                ->integer($cartItem->getAmount())
                    ->isEqualTo(2)
                ->datetime($cartItem->getCreatedAt())
                    ->isEqualTo(new \DateTime('2015-09-20T12:11:00+00:00'))
                ->array($cartItem->getData())
                    ->isEqualTo([
                        'when' => (new \DateTime('2015-09-20T15:00:00+00:00'))->format(DateTime::RFC3339),
                        'who' => 'John',
                    ])
                ->object($cartItem->getProduct())
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Product')
                ->string($cartItem->getProduct()->getId())
                    ->isEqualTo('/v1/products/10')
                ->array($cartItem->getCartItemDetailList())
                    ->isEmpty()
        ;

        $this->createNewInstance();
        $this
            ->given($data = [
                    '@id' => '/v1/cart_items/16',
                    'amount' => 2,
                    'cart' => [
                        '@id' => '/v1/carts/10',
                        'status' => 'waiting',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                    ],
                ])

            ->then
                ->object($cartItem = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\CartItem'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                ->object($cart = $cartItem->getCart())
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Cart')
                ->string($cart->getClientPhoneNumber())
                    ->isEqualTo('+33 1 23 45 67 89')
                ->string($cart->getId())
                    ->isEqualTo('/v1/carts/10')
                ->string($cart->getStatus())
                    ->isEqualTo('waiting')
        ;
    }

    public function testSerializeNullValues()
    {
        $this->createNewInstance();
        $this
            ->given($cart = $this->createNewCart())
                ->and($cart->setStatus(null))
                ->and($cart->setOrder(null))
            ->then
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        'status' => null,
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [],
                        'order' => null,
                    ])
        ;
    }

    public function testSerializingAttributeNameDiffThanPropertyName()
    {
        $this->createNewInstance();
        $this
            ->given($product = $this->createNewProduct())
            ->then
                ->array($data = $this->testedInstance->serialize($product, 'Mapado\RestClientSdk\Tests\Model\Product'))
                ->isIdenticalTo([
                    'product_value' => 8.2,
                    'currency' => 'eur',
                ])
        ;
    }

    public function testWeirdIdentifier()
    {
        $this->createNewInstance($this->getMapping('weirdId'));

        $this
            ->given($cart = $this->createCart())
                ->and($cartItem = $this->createKnownCartItem())
                ->and($cart->addCartItemList($cartItem))

            ->then
                ->array($data = $this->testedInstance->serialize(
                    $cart,
                    'Mapado\RestClientSdk\Tests\Model\Cart',
                    [ 'serializeRelations' => ['cart_items'] ]
                ))
                    ->isIdenticalTo([
                        'weirdId' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'weirdId' => '/v1/cart_items/16',
                                'amount' => 1,
                                'createdAt' => (new \DateTime('2015-11-04 15:13:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-11-04 15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'Jane',
                                ],
                                'cart' => '/v1/carts/8',
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            ->then
                ->array($data = $this->testedInstance->serialize(
                    $cart,
                    'Mapado\RestClientSdk\Tests\Model\Cart',
                    [ 'serializeRelations' => ['cart_items'] ]
                ))
                    ->isIdenticalTo([
                        'weirdId' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cart_items' => [
                            [
                                'weirdId' => '/v1/cart_items/16',
                                'amount' => 1,
                                'createdAt' => (new \DateTime('2015-11-04 15:13:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-11-04 15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'Jane',
                                ],
                                'cart' => '/v1/carts/8',
                                'product' => '/v1/products/10',
                                'cartItemDetailList' => [],
                            ],
                        ],
                        'order' => null,
                    ])

            // reverse the serialization
            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                ->string($cart->getId())
                    ->isEqualTo('/v1/carts/8')
                ->array($cart->getCartItemList())
                    ->size->isEqualTo(1)
                // ->object($cartItem = current($cart->getCartItemList()))
                //     ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\CartItem')
                // ->string($cartItem->getId())
                //     ->isEqualTo('/v1/cart_items/16')
            ;
    }

    public function testDeserializeWithExtraFields()
    {
        $this->createNewInstance();

        $this
            ->given($data = [
                '@foo' => 'bar',
                '@id' => '/v1/carts/8',
                'status' => 'payed',
                "clientPhoneNumber" => '+33 1 23 45 67 89',
                'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                'cart_items' => [],
                'order' => null,
            ])

            ->then
                ->object($cart = $this->testedInstance->deserialize($data, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\Cart')
        ;
    }

    public function testSerializingIriManyToOne()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../../cache/');
        $mapping = new Mapping();
        $mapping->setMapping($annotationDriver->loadDirectory(__DIR__ . '/../../Model/Issue46/'));

        $section = new Issue46\Section();
        $section->setId(46);
        $section->setIri('/sections/46');
        $section->setTitle('section title');

        $article = new Issue46\Article();
        $article->setSection($section);

        $this->createNewInstance($mapping);

        $this
            ->then
                ->array($this->testedInstance->serialize($article, 'Mapado\RestClientSdk\Tests\Model\Issue46\Article'))
                    ->isIdenticalTo([
                        'id' => null,
                        'section' => '/sections/46',
                    ])

                ->if($article->setIri('/articles/44'))

                ->array($this->testedInstance
                    ->serialize(
                        $section,
                        'Mapado\RestClientSdk\Tests\Model\Issue46\Section'
                    ))
                ->isIdenticalTo([
                    '@id' => '/sections/46',
                    'id' => 46,
                    'title' => 'section title',
                    'articleList' => [
                        '/articles/44',
                    ],
                ])

                ->array($this->testedInstance
                    ->serialize(
                        $section,
                        'Mapado\RestClientSdk\Tests\Model\Issue46\Section',
                        [ 'serializeRelations' => ['articleList'] ]
                    ))
                ->isIdenticalTo([
                    '@id' => '/sections/46',
                    'id' => 46,
                    'title' => 'section title',
                    'articleList' => [
                        [
                            '@id' => '/articles/44',
                            'id' => null,
                            'section' => '/sections/46',
                        ]
                    ],
                ])
        ;
    }

    /**
     * getMapping
     *
     * @access private
     * @return Mapping
     */
    private function getMapping($idKey = '@id')
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

    private function getProductMetadata($idKey)
    {
        $productMetadata = new ClassMetadata(
            'products',
            'Mapado\RestClientSdk\Tests\Model\Product',
            ''
        );

        $productMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('product_value', 'value'),
            new Attribute('currency'),
        ]);

        return $productMetadata;
    }

    private function getCartItemDetailMetadata($idKey)
    {
        $cartItemDetailMetadata = new ClassMetadata(
            'cart_item_details',
            'Mapado\RestClientSdk\Tests\Model\CartItemDetail',
            ''
        );

        $cartItemDetailMetadata->setRelationList([
            new Relation('cartItem', Relation::MANY_TO_ONE, 'Mapado\RestClientSdk\Tests\Model\CartItem'),
        ]);
        $cartItemDetailMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('name'),
            new Attribute('cartItem'),
        ]);

        return $cartItemDetailMetadata;
    }

    private function getCartItemMetadata($idKey)
    {
        $cartItemMetadata = new ClassMetadata(
            'cart_items',
            'Mapado\RestClientSdk\Tests\Model\CartItem',
            ''
        );

        $cartItemMetadata->setRelationList([
            new Relation('cart', Relation::MANY_TO_ONE, 'Mapado\RestClientSdk\Tests\Model\Cart'),
            new Relation('product', Relation::MANY_TO_ONE, 'Mapado\RestClientSdk\Tests\Model\Product'),
            new Relation('cartItemDetailList', Relation::ONE_TO_MANY, 'Mapado\RestClientSdk\Tests\Model\CartItemDetail'),
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

    private function getCartMetadata($idKey)
    {
        $cartMetadata = new ClassMetadata(
            'carts',
            'Mapado\RestClientSdk\Tests\Model\Cart',
            ''
        );
        $cartMetadata->setAttributeList([
            new Attribute($idKey, 'id', 'string', true),
            new Attribute('status'),
            new Attribute('clientPhoneNumber', 'clientPhoneNumber', 'phone_number'),
            new Attribute('createdAt', 'createdAt', 'datetime'),
            new Attribute('cart_items', 'cartItemList'),
            new Attribute('order'),
        ]);
        $cartMetadata->setRelationList([
            new Relation('cart_items', Relation::ONE_TO_MANY, 'Mapado\RestClientSdk\Tests\Model\CartItem'),
            new Relation('order', Relation::MANY_TO_ONE, 'Mapado\RestClientSdk\Tests\Model\Order'),
        ]);

        return $cartMetadata;
    }

    /**
     * createNewCart
     *
     * @access private
     * @return AbstractModel
     */
    private function createNewCart()
    {
        $cart = new \Mapado\RestClientSdk\Tests\Model\Cart();
        $cart->setStatus('payed');
        $cart->setCreatedAt(new DateTime('2015-09-20 12:08:00'));

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $clientPhoneNumber = $phoneNumberUtil->parse('+33123456789', PhoneNumberFormat::INTERNATIONAL);
        $cart->setClientPhoneNumber($clientPhoneNumber);

        return $cart;
    }

    /**
     * createCart
     *
     * @access private
     * @return void
     */
    private function createCart()
    {
        $cart = $this->createNewCart();
        $cart->setId('/v1/carts/8');

        return $cart;
    }

    /**
     * createKnownCartItem
     *
     * @access private
     * @return AbstractModel
     */
    private function createKnownCartItem()
    {
        $cartItem = $this->createNewCartItem();
        $cartItem->setId('/v1/cart_items/16');
        $cartItem->setAmount(1);
        $cartItem->setCreatedAt(new DateTime('2015-11-04 15:13:00'));
        $cartItem->setData([
            'when' => new DateTime('2015-11-04 15:00:00'),
            'who' => 'Jane',
        ]);
        $cartItem->setCart($this->createCart());

        return $cartItem;
    }

    /**
     * createNewCartItem
     *
     * @access private
     * @return AbstractModel
     */
    private function createNewCartItem($addKnownedProduct = true)
    {
        $cartItem = new \Mapado\RestClientSdk\Tests\Model\CartItem();
        $cartItem->setAmount(2);
        $cartItem->setCreatedAt(new DateTime('2015-09-20 12:11:00'));
        $cartItem->setData([
            'when' => new DateTime('2015-09-20 15:00:00'),
            'who' => 'John',
        ]);

        if ($addKnownedProduct) {
            $cartItem->setProduct($this->createKnownedProduct());
        }

        return $cartItem;
    }

    /**
     * createNewProduct
     *
     * @access private
     * @return AbstractModel
     */
    private function createNewProduct()
    {
        $product = new \Mapado\RestClientSdk\Tests\Model\Product();

        $product->setValue(8.2);
        $product->setCurrency('eur');

        return $product;
    }


    /**
     * createKnownedProduct
     *
     * @access private
     * @return AbstractModel
     */
    private function createKnownedProduct()
    {
        $product = $this->createNewProduct();
        $product->setId('/v1/products/10');

        return $product;
    }

    private function createNewCartItemDetail()
    {
        $item = new \Mapado\RestClientSdk\Tests\Model\CartItemDetail();

        $item->setName('Bill');

        return $item;
    }

    /**
     * createNewInstance
     *
     * @access private
     * @return void
     */
    private function createNewInstance($mapping = null)
    {
        $mapping = $mapping ?: $this->getMapping();
        $this->newTestedInstance($mapping);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $restClient = new \mock\Mapado\RestClientSdk\RestClient();
        $this->mockGenerator->unshuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient($restClient, $mapping, $this->testedInstance);
        $sdk->setFileCachePath(__DIR__ . '/../../cache/');

        $cartRepositoryMock = $this->getCartRepositoryMock($sdk, $restClient, 'Mapado\RestClientSdk\Tests\Model\Cart');

        $this->calling($sdk)->getRepository = function ($modelName) use ($cartRepositoryMock) {
            switch ($modelName) {
                case 'Mapado\RestClientSdk\Tests\Model\Cart':
                    return $cartRepositoryMock;
                default:
                    return null;
            }
        };

        $this->testedInstance->setSdk($sdk);
    }

    private function getCartRepositoryMock($sdk, $restClient, $modelName)
    {
        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $sdk,
            $restClient,
            $modelName
        );

        $_this = $this;

        $this->calling($repository)->find = function ($id) use ($_this) {
            return $_this->createCart();
        };

        return $repository;
    }
}
