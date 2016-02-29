<?php

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * Class EntityRepository
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EntityRepository extends atoum
{
    /**
     * testFind
     *
     * @param mixed $method
     * @access public
     * @return void
     */
    public function testFind()
    {
        $mapping = new Mapping('v12');
        $mapping->setMapping([
            new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\Model',
                'mock\Mapado\RestClientSdk\EntityRepository'
            )
        ]);

        $this->mockGenerator->orphanize('__construct');
        $mockedSdk = new \mock\Mapado\RestClientSdk\SdkClient();
        $this->calling($mockedSdk)->getMapping = $mapping;
        $mockedHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($mockedSdk);
        $this->calling($mockedSdk)->getModelHydrator = $mockedHydrator;

        $this->mockGenerator->orphanize('__construct');
        $mockedRestClient = new \mock\Mapado\RestClientSdk\RestClient();

        $this->calling($mockedSdk)->getRestClient = $mockedRestClient;
        $this->calling($mockedRestClient)->get = [];

        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $mockedSdk,
            $mockedRestClient,
            'Mapado\RestClientSdk\Tests\Model\Model'
        );

        $this
            ->if($repository->find('1'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1')->once()

            ->given($this->resetMock($mockedRestClient))
            ->if($repository->find('v12/orders/999'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/999')->once()

            ->if($repository->findAll())
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders')->once()

            ->if($repository->findOneByFoo('bar'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=bar')->once()
                ->mock($mockedHydrator)
                    ->call('hydrate')
                        ->twice()

            ->if($repository->findByFoo('baz'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders?foo=baz')->once()
                ->mock($mockedHydrator)
                    ->call('hydrateList')
                        ->twice()
        ;
    }
    /**
     * testFindNotFound
     *
     * @param mixed $method
     * @access public
     * @return void
     */
    public function testFindNotFound()
    {
        $mapping = new Mapping();
        $mapping->setMapping([
            new ClassMetadata(
                'orders',
                'Mapado\RestClientSdk\Tests\Model\Model',
                'mock\Mapado\RestClientSdk\EntityRepository'
            )
        ]);

        $this->mockGenerator->orphanize('__construct');
        $mockedSdk = new \mock\Mapado\RestClientSdk\SdkClient();
        $this->calling($mockedSdk)->getMapping = $mapping;
        $this->calling($mockedSdk)->getModelHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($mockedSdk);

        $this->mockGenerator->orphanize('__construct');
        $mockedRestClient = new \mock\Mapado\RestClientSdk\RestClient();

        $this->calling($mockedSdk)->getRestClient = $mockedRestClient;
        $this->calling($mockedRestClient)->get = null;

        $modelHydrator = new \mock\Mapado\RestClientSdk\Model\ModelHydrator($mockedSdk);

        $repository = new \mock\Mapado\RestClientSdk\EntityRepository(
            $mockedSdk,
            $mockedRestClient,
            'Mapado\RestClientSdk\Tests\Model\Model'
        );

        $this
            ->variable($repository->find('1'))
            ->isNull()
        ;
    }
}
