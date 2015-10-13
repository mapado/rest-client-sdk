<?php

namespace Mapado\RestClientSdk\Tests\Units\Client;

use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

use atoum;

/**
 * Class AbstractClient
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class AbstractClient extends atoum
{
    /**
     * beforeTestMethod
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
                'mock\Mapado\RestClientSdk\Client\AbstractClient'
            )
        ]);

        $this->mockGenerator->orphanize('__construct');
        $mockedSdk = new \mock\Mapado\RestClientSdk\SdkClient();
        $this->calling($mockedSdk)->getMapping = $mapping;

        $this->mockGenerator->orphanize('__construct');
        $mockedRestClient = new \mock\Mapado\RestClientSdk\RestClient();

        $this->calling($mockedSdk)->getRestClient = $mockedRestClient;
        $this->calling($mockedRestClient)->get = function () {
            return [];
        };

        $this->mockGenerator->orphanize('__construct');
        $mockedSerializer = new \mock\Mapado\RestClientSdk\Model\Serializer();
        $this->calling($mockedSerializer)->deserialize = null;
        $this->calling($mockedSdk)->getSerializer = $mockedSerializer;

        $abstractClient = new \mock\Mapado\RestClientSdk\Client\AbstractClient($mockedSdk);
        $this
            ->if($abstractClient->find('1'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/1')->once()

            ->given($this->resetMock($mockedRestClient))
            ->if($abstractClient->find('v12/orders/999'))
            ->then
                ->mock($mockedRestClient)
                    ->call('get')
                        ->withArguments('v12/orders/999')->once()
        ;
    }
}
