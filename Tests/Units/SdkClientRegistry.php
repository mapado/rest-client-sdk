<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use atoum;
use Mapado\RestClientSdk\Exception\SdkClientNotFoundException;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\SdkClient;

class SdkClientRegistry extends atoum
{
    public function testGetSdkClientList()
    {
        $this
            ->given($sdkClientList = $this->createSdkClientList(['foo', 'bar']))
            ->and($this->newTestedInstance($sdkClientList))
            ->then
            ->array($this->testedInstance->getSdkClientList())
                ->isIdenticalTo($sdkClientList)
        ;
    }

    public function testGetSdkClient()
    {
        $this
            ->given($sdkClientList = $this->createSdkClientList(['foo', 'bar']))
            ->and($this->newTestedInstance($sdkClientList))
            ->then
            ->object($this->testedInstance->getSdkClient('bar'))
                ->isInstanceOf(SdkClient::class)
                ->exception(function () {
                    $this->testedInstance->getSdkClient('barrrrr');
                })
                    ->isInstanceOf(SdkClientNotFoundException::class)
                    ->hasMessage('Sdk client not found for name barrrrr')
        ;
    }

    public function testGetSdkClientForClass()
    {
        $sdkClientList = $this->createSdkClientList(['foo', 'bar']);
        $fooMapping = $this->newMockInstance(Mapping::class);
        $barMapping = $this->newMockInstance(Mapping::class);
        $fooMapping->getMockController()->hasClassMetadata = function ($name) {
            return 0 === mb_strpos($name, 'Foo');
        };
        $barMapping->getMockController()->hasClassMetadata = function ($name) {
            return 0 === mb_strpos($name, 'Bar');
        };
        $sdkClientList['foo']->getMockController()->getMapping = $fooMapping;
        $sdkClientList['bar']->getMockController()->getMapping = $barMapping;

        $this
            ->given($this->newTestedInstance($sdkClientList))
            ->then
            ->object($this->testedInstance->getSdkClientForClass('Foo\Entity\A'))
                ->isInstanceOf(SdkClient::class)
                ->isIdenticalTo($sdkClientList['foo'])

            ->object($this->testedInstance->getSdkClientForClass('Bar\Entity\B'))
                ->isInstanceOf(SdkClient::class)
                ->isIdenticalTo($sdkClientList['bar'])

            ->exception(function () {
                $this->testedInstance->getSdkClientForClass('NotMapped\Entity\C');
            })
                ->isInstanceOf(SdkClientNotFoundException::class)
                ->hasMessage('Sdk client not found for entity class NotMapped\Entity\C')
        ;
    }

    /**
     * @param array<string> $nameList
     */
    private function createSdkClientList(array $nameList)
    {
        $sdkClientList = [];

        foreach ($nameList as $name) {
            $this->mockGenerator->orphanize('__construct');
            $this->mockGenerator->shuntParentClassCalls();
            $sdkClientList[$name] = $this->newMockInstance(SdkClient::class);
            $this->mockGenerator->unshuntParentClassCalls();
        }

        return $sdkClientList;
    }
}
