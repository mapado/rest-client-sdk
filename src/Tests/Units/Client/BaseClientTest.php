<?php

namespace Mapado\RestClientSdk\Tests\Units\Client;

use atoum;

/**
 * Class BaseClientTest
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class BaseClientTest extends atoum
{
    /**
     * sdk
     *
     * @var SdkClient
     * @access protected
     */
    protected $sdk;

    /**
     * restClient
     *
     * @var RestClient
     * @access protected
     */
    protected $restClient;

    /**
     * beforeTestMethod
     *
     * @param mixed $method
     * @access public
     * @return void
     */
    public function beforeTestMethod($method)
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $this->restClient = new \mock\Mapado\RestClientSdk\RestClient();
        $this->mockGenerator->unshuntParentClassCalls();

        $this->sdk = new \mock\Mapado\RestClientSdk\SdkClient($this->restClient);
    }
}
