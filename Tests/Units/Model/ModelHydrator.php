<?php

namespace Mapado\RestClientSdk\Tests\Units\Model;

use atoum;
use DateTime;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * Class ModelHydrator
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ModelHydrator extends atoum
{
    private $sdk;

    public function beforeTestMethod($method)
    {
        $fooMetadata = new ClassMetadata(
            'foo',
            'Acme\Foo',
            ''
        );

        $mapping = new Mapping('/v1');
        $mapping->setMapping([
            $fooMetadata,
        ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $this->sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($this->sdk)->getMapping = $mapping;
    }

    /**
     * testConvertId
     *
     * @access public
     * @return void
     */
    public function testConvertId()
    {
        $this
            ->given($this->newTestedInstance($this->sdk))
            ->then
                ->string($this->testedInstance->convertId(2, 'Acme\Foo'))
                    ->isEqualTo('/v1/foo/2')
                ->string($this->testedInstance->convertId('/v1/foo/2', 'Acme\Foo'))
                    ->isEqualTo('/v1/foo/2')
        ;
    }

    /**
     * testConvertIdWithoutMappingPrefix
     *
     * @access public
     * @return void
     */
    public function testConvertIdWithoutMappingPrefix()
    {
        $fooMetadata = new ClassMetadata(
            'foo',
            'Acme\Foo',
            ''
        );

        $mapping = new Mapping();
        $mapping->setMapping([
            $fooMetadata,
        ]);

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $sdk = new \mock\Mapado\RestClientSdk\SdkClient;
        $this->calling($sdk)->getMapping = $mapping;
        $this
            ->given($this->newTestedInstance($sdk))
            ->then
                ->string($this->testedInstance->convertId(2, 'Acme\Foo'))
                    ->isEqualTo('/foo/2')
                ->string($this->testedInstance->convertId('/foo/2', 'Acme\Foo'))
                    ->isEqualTo('/foo/2')
        ;
    }
}
