<?php

namespace Mapado\RestClientSdk\Tests\Units\Model;

use atoum;
use DateTime;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

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
                        'cartItemList' => [],
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
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cartItemList' => [
                            '/v1/cart_items/16',
                        ],
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
                        'cartItemList' => [
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
                        'cartItemList' => [
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
                        'cartItemList' => [
                            [
                                'amount' => 2,
                                'createdAt' => (new \DateTime('2015-09-20T12:11:00'))->format(DateTime::RFC3339),
                                'data' => [
                                    'when' => (new \DateTime('2015-09-20T15:00:00'))->format(DateTime::RFC3339),
                                    'who' => 'John',
                                ],
                                'cartItemDetailList' => [
                                    [ 'name' => 'Bill', ],
                                    [ 'name' => 'Bill', ],
                                ],
                            ],
                        ],
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
                ->array($data = $this->testedInstance->serialize($cart, 'Mapado\RestClientSdk\Tests\Model\Cart'))
                    ->isIdenticalTo([
                        '@id' => '/v1/carts/8',
                        'status' => 'payed',
                        'clientPhoneNumber' => '+33 1 23 45 67 89',
                        'createdAt' => (new \DateTime('2015-09-20T12:08:00'))->format(DateTime::RFC3339),
                        'cartItemList' => [
                            '/v1/cart_items/16',
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
                        'cartItemList' => [
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
                    'cartItemList' => [
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
                    ]
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


    /**
     * getMapping
     *
     * @access private
     * @return Mapping
     */
    private function getMapping()
    {
        $cartMetadata = new ClassMetadata(
            'carts',
            'Mapado\RestClientSdk\Tests\Model\Cart',
            ''
        );
        $cartMetadata->setAttributeList([
            new Attribute('id', 'string', true),
            new Attribute('status'),
            new Attribute('clientPhoneNumber', 'phone_number'),
            new Attribute('createdAt', 'datetime'),
            new Attribute('cartItemList'),
        ]);
        $cartMetadata->setRelationList([
            new Relation('cartItemList', Relation::ONE_TO_MANY, 'Mapado\RestClientSdk\Tests\Model\CartItem'),
        ]);

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
            new Attribute('id', 'string', true),
            new Attribute('amount'),
            new Attribute('createdAt', 'datetime'),
            new Attribute('data'),
            new Attribute('cart'),
            new Attribute('product'),
            new Attribute('cartItemDetailList'),
        ]);

        $productMetadata = new ClassMetadata(
            'products',
            'Mapado\RestClientSdk\Tests\Model\Product',
            ''
        );

        $productMetadata->setAttributeList([
            new Attribute('id', 'string', true),
            new Attribute('value'),
            new Attribute('currency'),
        ]);


        $cartItemDetailMetadata = new ClassMetadata(
            'cart_items',
            'Mapado\RestClientSdk\Tests\Model\CartItemDetail',
            ''
        );

        $cartItemDetailMetadata->setRelationList([
            new Relation('cartItem', Relation::MANY_TO_ONE, 'Mapado\RestClientSdk\Tests\Model\CartItem'),
        ]);
        $cartItemDetailMetadata->setAttributeList([
            new Attribute('id', 'string', true),
            new Attribute('name'),
            new Attribute('cartItem'),
        ]);


        $mapping = new Mapping('/v1');
        $mapping->setMapping([
            $cartMetadata,
            $cartItemMetadata,
            $cartItemDetailMetadata,
            $productMetadata,
        ]);

        return $mapping;
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
    private function createNewInstance()
    {
        $this->newTestedInstance($this->getMapping());

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $restClient = new \mock\Mapado\RestClientSdk\RestClient();
        $this->mockGenerator->unshuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient($restClient, $this->getMapping(), $this->testedInstance);

        $this->testedInstance->setSdk($sdk);
    }
}
