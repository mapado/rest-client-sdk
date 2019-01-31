<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Collection\Collection;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver;
use Mapado\RestClientSdk\UnitOfWork;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class EntityRepository
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EntityRepository extends atoum
{
    private $mockedRestClient;

    private $mockedSdk;

    private $mockedHydrator;

    private $repository;

    private $mapping;

    private $unitOfWork;

    public function beforeTestMethod($method)
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockedSdk = new \mock\Mapado\RestClientSdk\SdkClient();

        $this->mockGenerator->orphanize('__construct');
        $this->mockedRestClient = new \mock\Mapado\RestClientSdk\RestClient();
        // $this->resetMock($this->mockedRestClient);

        $this->mockedHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($this->mockedSdk);
        $this->calling($this->mockedSdk)->getModelHydrator = $this->mockedHydrator;

        $this->mapping = new RestMapping('v12');
        $this->mapping->setMapping([
            (new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Model',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ))->setAttributeList([new Attribute('id', null, null, true)]),
        ]);
        $this->unitOfWork = new UnitOfWork($this->mapping);

        $this->calling($this->mockedSdk)->getMapping = $this->mapping;

        $serializer = new \Mapado\RestClientSdk\Model\Serializer($this->mapping, $this->unitOfWork);
        $serializer->setSdk($this->mockedSdk);
        $this->calling($this->mockedSdk)->getSerializer = $serializer;

        $this->repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Model'
        );
    }

    /**
     * testFind
     */
    public function testFind()
    {
        $this->calling($this->mockedRestClient)->get = [];

        $this
            ->if($this->repository->find('1'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1')->once()

            ->given($this->resetMock($this->mockedRestClient))
            ->if($this->repository->find('v12/orders/999'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/999')->once()

            ->if($this->repository->findAll())
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders')->once()

            ->if($this->repository->findOneByFoo('bar'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar')->once()
                ->mock($this->mockedHydrator)
                    ->call('hydrate')
                        ->twice()

            ->if($this->repository->findByFoo('baz'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=baz')->once()
                ->mock($this->mockedHydrator)
                    ->call('hydrateList')
                        ->twice()
        ;
    }

    /**
     * testFindWithQueryParameters
     */
    public function testFindWithQueryParameters()
    {
        $this->calling($this->mockedRestClient)->get = [];

        $this
            ->if($this->repository->find('1', ['foo' => 'bar', 'bar' => 'baz']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1?foo=bar&bar=baz')->once()
        ;
    }

    /**
     * testFindWithCache
     */
    public function testFindWithCache()
    {
        $mockOrder1 = new \mock\entity();
        $mockOrder2 = new \mock\entity();
        $mockOrder3 = new \mock\entity();
        $this->calling($mockOrder1)->getId = 'v12/orders/1';
        $this->calling($mockOrder2)->getId = 'v12/orders/2';
        $this->calling($mockOrder3)->getId = 'v12/orders/3';

        $this->calling($this->mockedHydrator)->hydrate = $mockOrder1;
        $this->calling($this->mockedHydrator)->hydrateList = new Collection([$mockOrder1, $mockOrder2, $mockOrder3]);

        $arrayAdapter = new ArrayAdapter(0, false);
        $this->calling($this->mockedSdk)->getCacheItemPool = $arrayAdapter;
        $this->calling($this->mockedSdk)->getCachePrefix = 'test_prefix_';

        $this->calling($this->mockedRestClient)->get = [];

        $this->calling($this->mockedHydrator)->convertId[0] = 'v12/orders/1';
        $this->calling($this->mockedHydrator)->convertId[1] = 'v12/orders/1';
        $this->calling($this->mockedHydrator)->convertId[4] = 'v12/orders/3';

        $this
            ->if($this->repository->find(1))
            ->and($this->repository->find(1))
            ->and($this->repository->find(1, ['foo' => 'bar']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1')->once()
                    ->call('get')
                        ->withArguments('v12/orders/1?foo=bar')->once()

            // find all
            ->if($this->repository->findAll())
            ->and($this->repository->findAll())
            ->if($this->repository->find(3))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders')->once()
                    ->call('get')
                        ->withArguments('v12/orders/3')->never()

            // find by
            ->given($this->resetMock($this->mockedRestClient))
                ->and($this->mockedSdk->getCacheItemPool()->clear())

            ->if($this->repository->findBy(['foo' => 'bar', 'bar' => 'baz']))
            ->and($this->repository->findBy(['foo' => 'bar', 'bar' => 'baz']))
            ->if($this->repository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar&bar=baz')->once()
                    ->call('get')
                        ->withArguments('v12/orders/1')->never()

            // find by something
            ->given($this->resetMock($this->mockedRestClient))

            ->if($this->repository->findByBar('baz'))
                ->and($this->repository->findByBar('baz'))
                ->and($this->repository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?bar=baz')->once()
                    ->call('get')
                        ->withArguments('v12/orders/1')->never()

            // find one by
            ->given($this->resetMock($this->mockedRestClient))

            ->if($this->repository->findOneBy(['foo' => 'baz', 'bar' => 'bar']))
            ->and($this->repository->findOneBy(['foo' => 'baz', 'bar' => 'bar']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=baz&bar=bar')->once()

            // find one by thing
            ->given($this->resetMock($this->mockedRestClient))

            ->if($this->repository->findOneByFoo('bar'))
            ->and($this->repository->findOneByFoo('bar'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar')->once()

            // find one by with data already in cache
            ->given($this->resetMock($this->mockedRestClient))
            ->if($this->repository->findOneBy(['foo' => 'bar', 'bar' => 'baz']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar&bar=baz')->never()
        ;
    }

    /**
     * testClearCacheAfterUpdate
     */
    public function testClearCacheAfterUpdate()
    {
        $mapping = new RestMapping('/v12');
        $mapping->setMapping([
            (new ClassMetadata(
                'products',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Product',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ))->setAttributeList([
                new Attribute('id', null, null, true),
            ]),
        ]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $this->unitOfWork);

        $product1 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Product();
        $product2 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Product();
        $product3 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Product();
        $product1->setId('/v12/products/1');
        $product2->setId('/v12/products/2');
        $product3->setId('/v12/products/3');

        $this->calling($this->mockedHydrator)->hydrate = $product1;
        $this->calling($this->mockedHydrator)->hydrateList = [$product1, $product2, $product3];

        $arrayAdapter = new ArrayAdapter(0, false);
        $this->calling($this->mockedSdk)->getCacheItemPool = $arrayAdapter;
        $this->calling($this->mockedSdk)->getCachePrefix = 'test_prefix_';

        $this->calling($this->mockedRestClient)->get = ['id' => '/v12/products/1'];
        $this->calling($this->mockedRestClient)->put = ['id' => '/v12/products/1'];
        $this->calling($this->mockedRestClient)->delete = null;

        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Product'
        );

        $this
            ->if($repository->find(1))
            ->then
                ->boolean($arrayAdapter->hasItem('test_prefix__v12_products_1'))
                    ->isTrue()

            ->if($repository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/v12/products/1')->once()

            // after update
            ->if($repository->update($product1))
                ->boolean($arrayAdapter->hasItem('test_prefix__v12_products_1'))
                    ->isFalse()

            ->if($repository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/v12/products/1')->twice()

            // after deletion
            ->if($repository->remove($product1))
            ->then
                ->boolean($arrayAdapter->hasItem('test_prefix__v12_products_1'))
                    ->isFalse()
        ;
    }

    public function testPutWithoutStore()
    {
        $product1 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Order();
        $product1->setId('/v12/orders/1');

        $this->calling($this->mockedHydrator)->hydrate = $product1;
        $this->calling($this->mockedHydrator)->hydrateList = [$product1];

        $this->calling($this->mockedRestClient)->put = [$product1];
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($this->mapping, $this->unitOfWork);

        $this
            ->given($updatedProduct = $this->repository->update($product1))
            ->then
                ->object($updatedProduct)
                    ->isIdenticalTo($product1);
    }

    public function testCacheWithIriAsId()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping($annotationDriver->loadDirectory(__DIR__ . '/../Model/Issue46/'));

        $unitOfWork = new UnitOfWork($mapping);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $unitOfWork);

        $section1 = new \Mapado\RestClientSdk\Tests\Model\Issue46\Section();
        $section1->setIri('/sections/1');

        $this->calling($this->mockedHydrator)->hydrate = $section1;
        $this->calling($this->mockedHydrator)->hydrateList = new Collection([$section1]);

        $arrayAdapter = new ArrayAdapter(0, false);
        $this->calling($this->mockedSdk)->getCacheItemPool = $arrayAdapter;
        $this->calling($this->mockedSdk)->getCachePrefix = 'test_prefix_';

        $this->calling($this->mockedRestClient)->get = ['id' => '/sections/1'];
        $this->calling($this->mockedRestClient)->put = ['id' => '/sections/1'];
        $this->calling($this->mockedRestClient)->delete = null;

        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\Issue46\Section'
        );

        $this
            ->if($repository->findBy(['section' => $section1]))
           ->then
               ->mock($this->mockedRestClient)
                   ->call('get')
                       ->withArguments('/sections?section=%2Fsections%2F1')->once()

            ->if($repository->findAll())
            ->then
                ->boolean($arrayAdapter->hasItem('test_prefix__sections_1'))
                    ->isTrue()

           ->if($repository->find(1))
           ->then
               ->mock($this->mockedRestClient)
                   ->call('get')
                       ->withArguments('/sections/1')->never()

            // after update
            ->if($repository->update($section1))
                ->boolean($arrayAdapter->hasItem('test_prefix__sections_1'))
                    ->isFalse()
            ->then
                ->mock($this->mockedRestClient)
                    ->call('put')
                        ->withArguments('/sections/1')->once()

            ->if($repository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/sections/1')->once()

            // after deletion
            ->if($repository->remove($section1))
            ->then
                ->boolean($arrayAdapter->hasItem('test_prefix__sections_1'))
                    ->isFalse()
        ;
    }

    /**
     * testFindNotFound
     */
    public function testFindNotFound()
    {
        $this->calling($this->mockedRestClient)->get = null;

        $this
            ->variable($this->repository->find('1'))
            ->isNull()
        ;
    }

    public function testFindOneByObject()
    {
        $mapping = new RestMapping('v12');
        $mapping->setMapping([
            (new ClassMetadata(
                'carts',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ))->setAttributeList([
                new Attribute('id', null, null, true),
            ]),
            new ClassMetadata(
                'cart_items',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
        ]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->calling($this->mockedRestClient)->get = [];

        $cartItemRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
        );

        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setId(1);

        $this
            ->given($cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart())
                ->and($cart->setId(1))
            ->if($cartItemRepository->findOneByCart($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/cart_items?cart=1')->once()

            // test with unmapped class
            ->given($cart = new \mock\stdClass())
            ->if($cartItemRepository->findOneByCart($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/cart_items?')->once()
        ;
    }

    public function testWithoutMappingPrefix()
    {
        $mapping = new RestMapping('/v12');
        $mapping = new RestMapping();
        $mapping->setMapping([
            new ClassMetadata(
                'carts',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
            new ClassMetadata(
                'cart_items',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
        ]);

        $serializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $this->unitOfWork);
        $serializer->setSdk($this->mockedSdk);
        $this->calling($this->mockedSdk)->getSerializer = $serializer;
        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->calling($this->mockedRestClient)->get = [];
        $this->calling($this->mockedRestClient)->post = [];

        $cartItemRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
        );

        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setId(1);

        $this
            ->if($cartItemRepository->find(1))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/cart_items/1')->once()

            ->if($cartItemRepository->findAll())
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/cart_items')->once()

            ->if($cartItemRepository->findBy(['foo' => 'bar']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('/cart_items?foo=bar')->once()

            ->given($cartItem = new \mock\Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem())
            ->if($cartItemRepository->persist($cartItem))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('post')
                        ->withArguments('/cart_items')->once()
        ;
    }

    public function testFindOneByWithHal()
    {
        $mapping = new RestMapping('v12');
        $classMetadata = new ClassMetadata(
            'orders',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order',
            'mock\Mapado\RestClientSdk\EntityRepository'
        );
        $classMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
        ]);
        $mapping->setMapping([$classMetadata]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order'
        );

        $mapping->setConfig([
            'collectionKey' => 'fooList',
        ]);
        $this->calling($this->mockedRestClient)->get = [
            'fooList' => [
                [
                    '@id' => '/orders/2',
                ],
            ],
        ];
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $this->unitOfWork);

        $this
            ->then
                ->object($order = $this->repository->findOneBy(['a' => 'a']))
                    ->isInstanceOf('Mapado\RestClientSdk\Tests\Model\JsonLd\Order')
                ->string($order->getId())
                    ->isEqualTo('/orders/2')
        ;
    }

    /**
     * testFindOneByWithoutResult
     */
    public function testFindOneByWithoutResult()
    {
        $mapping = new RestMapping('v12');
        $classMetadata = new ClassMetadata(
            'orders',
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order',
            'mock\Mapado\RestClientSdk\EntityRepository'
        );
        $classMetadata->setAttributeList([
            new Attribute('@id', 'id', 'string', true),
        ]);
        $mapping->setMapping([$classMetadata]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $this->unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order'
        );

        $mapping->setConfig([
            'collectionKey' => 'fooList',
        ]);
        $this->calling($this->mockedRestClient)->get = [
            'fooList' => [
            ],
        ];
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $this->unitOfWork);

        $this
            ->then
                ->variable($order = $this->repository->findOneBy(['a' => 'a']))
                    ->isNull()
        ;
    }

    public function testPersistWithUnitOfWork()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping($annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/'));

        $unitOfWork = $this->newMockInstance(UnitOfWork::class, null, null, [$mapping]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $unitOfWork);

        $cartItemRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
        );
        $cartRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setStatus('pending');
        $cartItem = new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem();
        $cartItem->setCart($cart);
        $cartItem->setAmount(0);
        $cartItem2 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem();
        $cartItem2->setCart($cart);
        $cartItem2->setData(['foo' => 'bar']);

        $this->calling($this->mockedRestClient)->post = [];

        $this
            ->if($cartRepository->persist($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('post')
                        ->withArguments(
                            '/cart',
                            [
                                'status' => 'pending',
                                'cart_items' => [
                                    [
                                        'amount' => 0,
                                        'data' => [],
                                    ],
                                    [
                                        'data' => ['foo' => 'bar'],
                                    ],
                                ],
                            ]
                        )
                        ->once()
        ;
    }

    public function testUpdatingInstanceDoesGetDataFromUnitOfWork()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping($annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/'));

        $unitOfWork = $this->newMockInstance(UnitOfWork::class, null, null, [$mapping]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $unitOfWork);

        $cartRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->calling($this->mockedRestClient)->get = [
            'id' => '/v1/carts/1',
            'status' => 'pending',
            'created_at' => '2019-01-01',
        ];

        $this->calling($this->mockedRestClient)->put = [
            'id' => '/v1/carts/1',
            'status' => 'payed',
            'created_at' => '2019-01-01',
        ];

        $this
            ->if($cart = $cartRepository->find('/v1/carts/1'))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments(
                            '/v1/carts/1'
                        )
                        ->once()
                ->mock($unitOfWork)
                    ->call('registerClean')
                        ->withArguments('/v1/carts/1')->exactly(2)
                        ->withAnyArguments()->exactly(2)

            ->if($cart->setStatus('payed'))
            ->if($cartRepository->update($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('put')
                        ->withArguments(
                            '/v1/carts/1',
                            ['status' => 'payed']
                        )
                        ->once()
                ->mock($unitOfWork)
                    ->call('registerClean')
                        ->withArguments('/v1/carts/1')->exactly(3)
                        ->withAnyArguments()->exactly(3)
        ;
    }

    public function testUpdatingInstanceDoesGetDataFromUnitOfWorkWithQueryParam()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping($annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/'));

        $unitOfWork = $this->newMockInstance(UnitOfWork::class, null, null, [$mapping]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping, $unitOfWork);

        $cartRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            $unitOfWork,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart'
        );

        $this->calling($this->mockedRestClient)->get = [
            'id' => '/v1/carts/1',
            'status' => 'pending',
        ];

        $this->calling($this->mockedRestClient)->put = [
            'id' => '/v1/carts/1',
            'status' => 'payed',
        ];

        $this
            ->if($cart = $cartRepository->find('/v1/carts/1', ['fields' => 'id,status']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments(
                            '/v1/carts/1?fields=id%2Cstatus'
                        )
                        ->once()
                ->mock($unitOfWork)
                    ->call('registerClean')
                        ->withArguments('/v1/carts/1')->exactly(1)
                        ->withArguments('/v1/carts/1?fields=id%2Cstatus')->exactly(1)
                        ->withAnyArguments()->exactly(2)

            ->if($cart->setStatus('payed'))
            ->if($cartRepository->update($cart, [], ['fields' => 'id']))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('put')
                        ->withArguments(
                            '/v1/carts/1?fields=id',
                            ['status' => 'payed']
                        )
                        ->once()
                ->mock($unitOfWork)
                    ->call('registerClean')
                        ->withArguments('/v1/carts/1')->exactly(2)
                        ->withArguments('/v1/carts/1?fields=id%2Cstatus')->exactly(1)
                        ->withAnyArguments()->exactly(3)
        ;
    }
}
