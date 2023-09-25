<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use Mapado\RestClientSdk\Collection\Collection;
use Mapado\RestClientSdk\EntityRepository;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver;
use Mapado\RestClientSdk\Model\ModelHydrator;
use Mapado\RestClientSdk\RestClient;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\Tests\Model\JsonLd\Order;
use Mapado\RestClientSdk\UnitOfWork;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @covers EntityRepository
 */
class EntityRepositoryTest extends TestCase
{
    #[DataProvider('findDataProvider')]
    public function testFindUs(callable $doFind, string $path)
    {
        [
            'restClient' => $mockedRestClient,
            'repository' => $repository,
        ] = $this->getRepository();

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($path)
            ->willReturn([]);

        $doFind($repository);
    }

    public static function findDataProvider(): iterable
    {
        yield [
            fn(EntityRepository $repository) => $repository->find('1'),
            'v12/orders/1',
        ];
        yield [
            fn(EntityRepository $repository) => $repository->find(
                'v12/orders/999'
            ),
            'v12/orders/999',
        ];

        yield [
            fn(EntityRepository $repository) => $repository->findAll(),
            'v12/orders',
        ];

        yield [
            fn(EntityRepository $repository) => $repository->findOneByFoo(
                'bar'
            ),
            'v12/orders?foo=bar',
        ];

        yield [
            fn(EntityRepository $repository) => $repository->findByFoo('baz'),
            'v12/orders?foo=baz',
        ];
    }

    public function testFindWithQueryParameters(): void
    {
        [
            'restClient' => $mockedRestClient,
            'repository' => $repository,
        ] = $this->getRepository();

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with('v12/orders/1?foo=bar&bar=baz')
            ->willReturn([]);

        $repository->find('1', ['foo' => 'bar', 'bar' => 'baz']);
    }

    public function testFindWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository();

        $mockedRestClient
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn([]);

        $repository->find(1);
        $repository->find(1, ['foo' => 'bar']);
    }

    public function testFindAllWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(mockHydrator: true);

        $mockOrder1 = $this->createMock(Order::class);
        $mockOrder1->method('getId')->willReturn('v12/orders/1');
        $mockOrder2 = $this->createMock(Order::class);
        $mockOrder2->method('getId')->willReturn('v12/orders/2');
        $mockOrder3 = $this->createMock(Order::class);
        $mockOrder3->method('getId')->willReturn('v12/orders/3');

        $mockedHydrator
            ->expects($this->once())
            ->method('hydrateList')
            ->willReturn(
                new Collection([$mockOrder1, $mockOrder2, $mockOrder3])
            );

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('v12/orders'));

        $repository->findAll();
        $repository->find(3);
    }

    public function testFindByWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(mockHydrator: true);

        $mockOrder1 = $this->createMock(Order::class);
        $mockOrder1->method('getId')->willReturn('v12/orders/1');
        $mockOrder2 = $this->createMock(Order::class);
        $mockOrder2->method('getId')->willReturn('v12/orders/2');
        $mockOrder3 = $this->createMock(Order::class);
        $mockOrder3->method('getId')->willReturn('v12/orders/3');

        $mockedHydrator
            ->expects($this->once())
            ->method('hydrateList')
            ->willReturn(
                new Collection([$mockOrder1, $mockOrder2, $mockOrder3])
            );

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('v12/orders?foo=bar&bar=baz'));

        $repository->findBy(['foo' => 'bar', 'bar' => 'baz']);
        $repository->findBy(['foo' => 'bar', 'bar' => 'baz']);
        $repository->find(1);
    }

    public function testFindBySomethingWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(mockHydrator: true);

        $mockOrder1 = $this->createMock(Order::class);
        $mockOrder1->method('getId')->willReturn('v12/orders/1');
        $mockOrder2 = $this->createMock(Order::class);
        $mockOrder2->method('getId')->willReturn('v12/orders/2');
        $mockOrder3 = $this->createMock(Order::class);
        $mockOrder3->method('getId')->willReturn('v12/orders/3');

        $mockedHydrator
            ->expects($this->once())
            ->method('hydrateList')
            ->willReturn(
                new Collection([$mockOrder1, $mockOrder2, $mockOrder3])
            );

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('v12/orders?bar=baz'));

        $repository->findByBar('baz');
        $repository->findByBar('baz');
        $repository->find(1);
    }

    public function testFindOneByWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(mockHydrator: true);

        $mockOrder1 = $this->createMock(Order::class);
        $mockOrder1->method('getId')->willReturn('v12/orders/1');

        $mockedHydrator
            ->expects($this->once())
            ->method('hydrate')
            ->willReturn($mockOrder1);

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('v12/orders?foo=baz&bar=bar'))
            ->willReturn(['hydra:member' => [['@id' => '/v12/orders/1']]]);

        $repository->findOneBy(['foo' => 'baz', 'bar' => 'bar']);
        $repository->findOneBy(['foo' => 'baz', 'bar' => 'bar']);
    }

    public function testFindOneBySomethingWithCache(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository();

        $mockOrder1 = $this->createMock(Order::class);
        $mockOrder1->method('getId')->willReturn('v12/orders/1');

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('v12/orders?foo=bar'))
            ->willReturn(['hydra:member' => [['id' => '/v12/orders/1']]]);

        $repository->findOneByFoo('bar');
        $repository->findOneByFoo('bar');
        $repository->findOneBy(['foo' => 'bar']);
    }

    public function testClearCacheAfterUpdate(): void
    {
        $mapping = new RestMapping('/v12');
        $mapping->setMapping([
            (new ClassMetadata(
                'products',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Product',
                'Mapado\RestClientSdk\EntityRepository'
            ))->setAttributeList([new Attribute('id', null, null, true)]),
        ]);

        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'cacheItemPool' => $arrayAdapter,
        ] = $this->getRepository($mapping);

        $product1 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Product();
        $product1->setId('/v12/products/1');

        $mockedRestClient
            ->expects($this->exactly(2))
            ->method('get')
            ->with('/v12/products/1')
            ->willReturn(['id' => '/v12/products/1']);

        $repository->find(1);
        $this->assertTrue(
            $arrayAdapter->hasItem('test_prefix__v12_products_1')
        );

        $repository->find(1);

        // after update
        $mockedRestClient
            ->method('put')
            ->willReturn(['id' => '/v12/products/1']);

        $repository->update($product1);
        $this->assertFalse(
            $arrayAdapter->hasItem('test_prefix__v12_products_1')
        );

        $repository->find(1);

        // after deletion
        $repository->remove($product1);
        $this->assertFalse(
            $arrayAdapter->hasItem('test_prefix__v12_products_1')
        );
    }

    public function testPutWithoutStore(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(mockHydrator: true);

        $product1 = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Order();
        $product1->setId('/v12/orders/1');

        $mockedHydrator->method('hydrate')->willReturn($product1);
        $mockedHydrator
            ->method('hydrateList')
            ->willReturn(new Collection([$product1]));

        $mockedRestClient->method('put')->willReturn([$product1]);

        $updatedProduct = $repository->update($product1);
        $this->assertSame($product1, $updatedProduct);
    }

    public function testCacheWithIriAsId(): void
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');

        $mapping = new RestMapping();
        $mapping->setMapping(
            $annotationDriver->loadDirectory(__DIR__ . '/../Model/Issue46/')
        );

        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'cacheItemPool' => $arrayAdapter,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(
            $mapping,
            'Mapado\RestClientSdk\Tests\Model\Issue46\Section',
            mockHydrator: true
        );

        $mockedRestClient->method('get')->willReturn(['id' => '/sections/1']);
        $mockedRestClient->method('put')->willReturn(['id' => '/sections/1']);

        $section1 = new \Mapado\RestClientSdk\Tests\Model\Issue46\Section();
        $section1->setIri('/sections/1');

        $mockedHydrator->method('hydrate')->willReturn($section1);
        $mockedHydrator
            ->method('hydrateList')
            ->willReturn(new Collection([$section1]));

        $mockedRestClient
            ->expects($this->exactly(3))
            ->method('get')
            ->with(
                $this->logicalOr(
                    // triggered with `findBy`
                    $this->identicalTo('/sections?section=%2Fsections%2F1'),
                    // triggered with `find`
                    $this->identicalTo('/sections'),
                    // trigger after update
                    $this->identicalTo('/sections/1')
                )
            );

        $repository->findBy(['section' => $section1]);

        $repository->findAll();

        $this->assertTrue($arrayAdapter->hasItem('test_prefix__sections_1'));

        // do not call /sections/1 here
        $repository->find(1);

        // after update
        $mockedRestClient
            ->expects($this->once())
            ->method('put')
            ->with($this->identicalTo('/sections/1'));
        $repository->update($section1);
        $this->assertFalse($arrayAdapter->hasItem('test_prefix__sections_1'));

        $repository->find(1);

        // after deletion
        $repository->remove($section1);
        $this->assertFalse($arrayAdapter->hasItem('test_prefix__sections_1'));
    }

    public function testFindNotFound(): void
    {
        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository();

        $mockedRestClient->method('get')->willReturn(null);

        $this->assertNull($repository->find('1'));
    }

    #[DataProvider('findOneByObjectDataProvider')]
    public function testFindOneByObject(
        object $cart,
        string $expectedPath
    ): void {
        $mapping = new RestMapping('v12');
        $mapping->setMapping([
            (new ClassMetadata(
                'carts',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ))->setAttributeList([new Attribute('id', null, null, true)]),
            new ClassMetadata(
                'cart_items',
                'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
        ]);

        [
            'repository' => $cartItemRepository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository(
            $mapping,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem'
        );

        $mockedRestClient->method('get')->willReturn([]);

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with($expectedPath);

        $cartItemRepository->findOneByCart($cart);
    }

    public static function findOneByObjectDataProvider(): iterable
    {
        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setId(1);

        yield [$cart, 'v12/cart_items?cart=1'];

        // unmapped class
        yield [new \stdClass(), 'v12/cart_items?'];
    }

    #[DataProvider('withoutMappingPrefixDataProvider')]
    public function testWithoutMappingPrefix(
        callable $doFind,
        string $method,
        string $expectedPath
    ): void {
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

        [
            'repository' => $cartItemRepository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository(
            $mapping,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem',
            mockHydrator: true
        );

        $mockedRestClient->method('get')->willReturn([]);
        $mockedRestClient->method('post')->willReturn([]);
        $mockedHydrator
            ->method('hydrate')
            ->willReturn(
                new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem()
            );

        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setId(1);

        $mockedRestClient
            ->expects($this->once())
            ->method($method)
            ->with($expectedPath);

        $doFind($cartItemRepository);
    }

    public static function withoutMappingPrefixDataProvider(): iterable
    {
        yield [
            fn($cartItemRepository) => $cartItemRepository->find(1),
            'get',
            '/cart_items/1',
        ];

        yield [
            fn($cartItemRepository) => $cartItemRepository->findAll(),
            'get',
            '/cart_items',
        ];

        yield [
            fn($cartItemRepository) => $cartItemRepository->findBy([
                'foo' => 'bar',
            ]),
            'get',
            '/cart_items?foo=bar',
        ];

        yield [
            fn($cartItemRepository) => $cartItemRepository->persist(
                new \Mapado\RestClientSdk\Tests\Model\JsonLd\CartItem()
            ),
            'post',
            '/cart_items',
        ];
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
        $mapping->setConfig([
            'collectionKey' => 'fooList',
        ]);

        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository($mapping);

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with('v12/orders?a=a')
            ->willReturn([
                'fooList' => [
                    [
                        '@id' => '/orders/2',
                    ],
                ],
            ]);

        $order = $repository->findOneBy(['a' => 'a']);

        $this->assertInstanceOf(
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Order',
            $order
        );
        $this->assertSame('/orders/2', $order->getId());
    }

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

        $mapping->setConfig([
            'collectionKey' => 'fooList',
        ]);

        [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'hydrator' => $mockedHydrator,
        ] = $this->getRepository($mapping, mockHydrator: true);

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->willReturn([
                'fooList' => [],
            ]);

        $mockedHydrator->expects($this->never())->method('hydrate');

        $order = $repository->findOneBy(['a' => 'a']);

        $this->assertNull($order);
    }

    public function testPersistWithUnitOfWork()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping(
            $annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/')
        );

        [
            'repository' => $cartRepository,
            'restClient' => $mockedRestClient,
        ] = $this->getRepository(
            $mapping,
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

        $mockedRestClient
            ->expects($this->once())
            ->method('post')
            ->with('/cart', [
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
            ])
            ->willReturn([]);

        $cartRepository->persist($cart);
    }

    public function testUpdatingInstanceDoesGetDataFromUnitOfWork()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping(
            $annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/')
        );

        [
            'repository' => $cartRepository,
            'restClient' => $mockedRestClient,
            'unitOfWork' => $unitOfWork,
        ] = $this->getRepository(
            $mapping,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            mockUnitOfWork: true
        );

        $cart = new \Mapado\RestClientSdk\Tests\Model\JsonLd\Cart();
        $cart->setId('/v1/carts/1');
        $cart->setStatus('pending');
        $cart->setCreatedAt(new \DateTime('2019-01-01'));

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with('/v1/carts/1')
            ->willReturn([
                'id' => '/v1/carts/1',
                'status' => 'pending',
                'created_at' => '2019-01-01',
            ]);

        // two times in `get` and one time after `update`
        $unitOfWork
            ->expects($this->exactly(3))
            ->method('registerClean')
            ->with('/v1/carts/1');

        $cart = $cartRepository->find('/v1/carts/1');

        $unitOfWork
            ->expects($this->once())
            ->method('getDirtyEntity')
            ->with('/v1/carts/1')
            ->willReturn(clone $cart);

        $mockedRestClient
            ->expects($this->once())
            ->method('put')
            ->with('/v1/carts/1', [
                'status' => 'payed',
            ])
            ->willReturn([
                'id' => '/v1/carts/1',
                'status' => 'payed',
                'created_at' => '2019-01-01',
            ]);

        $cart->setStatus('payed');

        $cartRepository->update($cart);
    }

    public function testUpdatingInstanceDoesGetDataFromUnitOfWorkWithQueryParam()
    {
        $annotationDriver = new AnnotationDriver(__DIR__ . '/../cache/');
        $mapping = new RestMapping();
        $mapping->setMapping(
            $annotationDriver->loadDirectory(__DIR__ . '/../Model/JsonLd/')
        );

        [
            'repository' => $cartRepository,
            'restClient' => $mockedRestClient,
            'unitOfWork' => $unitOfWork,
        ] = $this->getRepository(
            $mapping,
            'Mapado\RestClientSdk\Tests\Model\JsonLd\Cart',
            mockUnitOfWork: true
        );

        $mockedRestClient
            ->expects($this->once())
            ->method('get')
            ->with('/v1/carts/1?fields=id%2Cstatus')
            ->willReturn([
                'id' => '/v1/carts/1',
                'status' => 'pending',
            ]);

        $mockedRestClient->method('put')->willReturn([
            'id' => '/v1/carts/1',
            'status' => 'payed',
        ]);

        // get will trigger `/v1/carts/1` one time and `/v1/carts/1?fields=id%2Cstatus` one time
        // update will trigger `/v1/carts/1` one time
        $unitOfWork
            ->expects($this->exactly(3))
            ->method('registerClean')
            ->with(
                $this->logicalOr(
                    // is there a way in phpunit to test that each call are made ??
                    $this->identicalTo('/v1/carts/1'),
                    $this->identicalTo('/v1/carts/1?fields=id%2Cstatus')
                )
            );
        $cart = $cartRepository->find('/v1/carts/1', [
            'fields' => 'id,status',
        ]);

        $unitOfWork
            ->expects($this->once())
            ->method('getDirtyEntity')
            ->willReturn(clone $cart);

        $mockedRestClient
            ->expects($this->once())
            ->method('put')
            ->with('/v1/carts/1?fields=id', [
                'status' => 'payed',
            ]);

        $cart->setStatus('payed');
        $cartRepository->update($cart, [], ['fields' => 'id']);
    }

    /**
     * @return array{
     *   repository: EntityRepository,
     *   restClient: RestClient,
     *   cacheItemPool: ArrayAdapter,
     *   hydrator: ModelHydrator,
     *  unitOfWork: UnitOfWork
     * }
     */
    private function getRepository(
        ?RestMapping $mapping = null,
        ?string $modelName = null,
        bool $mockHydrator = false,
        bool $mockUnitOfWork = false
    ): array {
        if (!$mapping) {
            $mapping = new RestMapping('v12');
            $mapping->setMapping([
                (new ClassMetadata(
                    'orders',
                    'Mapado\RestClientSdk\Tests\Model\JsonLd\Model',
                    'mock\Mapado\RestClientSdk\EntityRepository'
                ))->setAttributeList([new Attribute('id', null, null, true)]),
            ]);
        }

        // extract first model name from mapping
        $modelName =
            $modelName ?? $mapping->getModelName($mapping->getMappingKeys()[0]);

        $mockedSdk = $this->createMock(SdkClient::class);
        $mockedRestClient = $this->createMock(RestClient::class);
        if ($mockHydrator) {
            $hydrator = $this->getMockBuilder(ModelHydrator::class)
                ->setConstructorArgs([$mockedSdk])
                ->onlyMethods(['hydrate', 'hydrateList'])
                ->getMock();
        } else {
            $hydrator = new ModelHydrator($mockedSdk);
        }

        $mockedSdk->method('getModelHydrator')->willReturn($hydrator);

        if ($mockUnitOfWork) {
            $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
                ->setConstructorArgs([$mapping])
                ->onlyMethods(['registerClean', 'getDirtyEntity'])
                ->getMock();
        } else {
            $unitOfWork = new UnitOfWork($mapping);
        }

        $mockedSdk->method('getMapping')->willReturn($mapping);
        $mockedSdk
            ->method('getSerializer')
            ->willReturn(
                new \Mapado\RestClientSdk\Model\Serializer(
                    $mapping,
                    $unitOfWork
                )
            );

        $arrayAdapter = new ArrayAdapter(0, false);
        $mockedSdk->method('getCacheItemPool')->willReturn($arrayAdapter);
        $mockedSdk->method('getCachePrefix')->willReturn('test_prefix_');

        // $mockedRestClient->method('delete')->willReturn(null);

        $repository = new EntityRepository(
            $mockedSdk,
            $mockedRestClient,
            $unitOfWork,
            $modelName
        );

        return [
            'repository' => $repository,
            'restClient' => $mockedRestClient,
            'cacheItemPool' => $arrayAdapter,
            'hydrator' => $hydrator,
            'unitOfWork' => $unitOfWork,
        ];
    }
}
