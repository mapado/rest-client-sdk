<?php

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping as RestMapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class EntityRepository
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EntityRepository extends atoum
{
    private $mockedRestClient;

    private $mockedSdk;

    private $mockedHydrator;

    private $repository;

    public function beforeTestMethod($method)
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockedSdk = new \mock\Mapado\RestClientSdk\SdkClient();
        $mockedHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($this->mockedSdk);
        $this->calling($this->mockedSdk)->getModelHydrator = $mockedHydrator;

        $this->mockGenerator->orphanize('__construct');
        $this->mockedRestClient = new \mock\Mapado\RestClientSdk\RestClient();
        // $this->resetMock($this->mockedRestClient);

        $this->mockedHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($this->mockedSdk);
        $this->calling($this->mockedSdk)->getModelHydrator = $this->mockedHydrator;

        $mapping = new RestMapping('v12');
        $mapping->setMapping([
            new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\Model',
                'mock\Mapado\RestClientSdk\EntityRepository'
            )
        ]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            'Mapado\RestClientSdk\Tests\Model\Model'
        );
    }

    /**
     * testFind
     *
     * @access public
     * @return void
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
     *
     * @access public
     * @return void
     */
    public function testFindWithQueryParameters()
    {
        $this->calling($this->mockedRestClient)->get = [];

        $this
            ->if($this->repository->find('1', [ 'foo' => 'bar', 'bar'  => 'baz' ]))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1?foo=bar&bar=baz')->once()
        ;
    }

    /**
     * testFindWithCache
     *
     * @param mixed $method
     * @access public
     * @return void
     */
    public function testFindWithCache()
    {
        $mockOrder1 = new \mock\entity;
        $mockOrder2 = new \mock\entity;
        $mockOrder3 = new \mock\entity;
        $this->calling($mockOrder1)->getId = 'v12/orders/1';
        $this->calling($mockOrder2)->getId = 'v12/orders/2';
        $this->calling($mockOrder3)->getId = 'v12/orders/3';

        $this->calling($this->mockedHydrator)->hydrate = $mockOrder1;
        $this->calling($this->mockedHydrator)->hydrateList = [$mockOrder1, $mockOrder2, $mockOrder3];

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

            ->if($this->repository->findBy([ 'foo' => 'bar', 'bar'  => 'baz' ]))
            ->and($this->repository->findBy([ 'foo' => 'bar', 'bar'  => 'baz' ]))
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

            ->if($this->repository->findOneBy([ 'foo' => 'baz', 'bar'  => 'bar' ]))
            ->and($this->repository->findOneBy([ 'foo' => 'baz', 'bar'  => 'bar' ]))
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
            ->if($this->repository->findOneBy([ 'foo' => 'bar', 'bar'  => 'baz' ]))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar&bar=baz')->never()
        ;
    }

    /**
     * testClearCacheAfterUpdate
     *
     * @access public
     *
     * @return void
     */
    public function testClearCacheAfterUpdate()
    {
        $mapping = new RestMapping('/v12');
        $mapping->setMapping([
            new ClassMetadata(
                'products',
                'Mapado\RestClientSdk\Tests\Model\Product',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
        ]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;
        $this->calling($this->mockedSdk)->getSerializer = new \Mapado\RestClientSdk\Model\Serializer($mapping);


        $product1 = new \Mapado\RestClientSdk\Tests\Model\Product;
        $product2 = new \Mapado\RestClientSdk\Tests\Model\Product;
        $product3 = new \Mapado\RestClientSdk\Tests\Model\Product;
        $product1->setId('/v12/products/1');
        $product2->setId('/v12/products/2');
        $product3->setId('/v12/products/3');

        $this->calling($this->mockedHydrator)->hydrate = $product1;
        $this->calling($this->mockedHydrator)->hydrateList = [$product1, $product2, $product3];

        $arrayAdapter = new ArrayAdapter(0, false);
        $this->calling($this->mockedSdk)->getCacheItemPool = $arrayAdapter;
        $this->calling($this->mockedSdk)->getCachePrefix = 'test_prefix_';

        $this->calling($this->mockedRestClient)->get = $product1;
        $this->calling($this->mockedRestClient)->put = $product1;
        $this->calling($this->mockedRestClient)->delete = null;

        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            'Mapado\RestClientSdk\Tests\Model\Product'
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

    /**
     * testFindNotFound
     *
     * @access public
     * @return void
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
            new ClassMetadata(
                'carts',
                'Mapado\RestClientSdk\Tests\Model\Cart',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
            new ClassMetadata(
                'cart_items',
                'Mapado\RestClientSdk\Tests\Model\CartItem',
                'mock\Mapado\RestClientSdk\EntityRepository'
            ),
        ]);

        $this->calling($this->mockedSdk)->getMapping = $mapping;

        $this->calling($this->mockedRestClient)->get = [];

        $cartItemRepository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $this->mockedSdk,
            $this->mockedRestClient,
            'Mapado\RestClientSdk\Tests\Model\CartItem'
        );


        $cart = new \Mapado\RestClientSdk\Tests\Model\Cart;
        $cart->setId(1);

        $this
            ->given($cart = new \Mapado\RestClientSdk\Tests\Model\Cart)
                ->and($cart->setId(1))
            ->if($cartItemRepository->findOneByCart($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/cart_items?cart=1')->once()

            // test with unmapped class
            ->given($cart = new \mock\stdClass)
            ->if($cartItemRepository->findOneByCart($cart))
            ->then
                ->mock($this->mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/cart_items?')->once()
        ;
    }
}
