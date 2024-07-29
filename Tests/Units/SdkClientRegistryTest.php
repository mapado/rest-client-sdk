<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units;

use Mapado\RestClientSdk\Exception\SdkClientNotFoundException;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\SdkClientRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SdkClientRegistry
 */
class SdkClientRegistryTest extends TestCase
{
    public function testGetSdkClientList(): void
    {
        $sdkClientList = $this->createSdkClientList(['foo', 'bar']);
        $testedInstance = new SdkClientRegistry($sdkClientList);

        $this->assertSame($sdkClientList, $testedInstance->getSdkClientList());
    }

    public function testGetSdkClient(): void
    {
        $sdkClientList = $this->createSdkClientList(['foo', 'bar']);
        $testedInstance = new SdkClientRegistry($sdkClientList);

        $this->assertInstanceOf(SdkClient::class, $testedInstance->getSdkClient('bar'));

        $this->expectException(SdkClientNotFoundException::class);
        $this->expectExceptionMessage('Sdk client not found for name barrrrr');
        $testedInstance->getSdkClient('barrrrr');
    }

    public function testGetSdkClientForClass(): void
    {
        $sdkClientList = $this->createSdkClientList(['foo', 'bar']);
        $fooMapping = $this->createMock(Mapping::class);
        $barMapping = $this->createMock(Mapping::class);
        $fooMapping->method('hasClassMetadata')->willReturnCallback(fn ($name) => 0 === mb_strpos($name, 'Foo'));
        $barMapping->method('hasClassMetadata')->willReturnCallback(fn ($name) => 0 === mb_strpos($name, 'Bar'));
        $sdkClientList['foo']->method('getMapping')->willReturn($fooMapping);
        $sdkClientList['bar']->method('getMapping')->willReturn($barMapping);

        $testedInstance = new SdkClientRegistry($sdkClientList);

        $this->assertInstanceOf(SdkClient::class, $testedInstance->getSdkClientForClass('Foo\Entity\A'));
        $this->assertSame($sdkClientList['foo'], $testedInstance->getSdkClientForClass('Foo\Entity\A'));

        $this->assertInstanceOf(SdkClient::class, $testedInstance->getSdkClientForClass('Bar\Entity\B'));
        $this->assertSame($sdkClientList['bar'], $testedInstance->getSdkClientForClass('Bar\Entity\B'));

        $this->expectException(SdkClientNotFoundException::class);
        $this->expectExceptionMessage('Sdk client not found for entity class NotMapped\Entity\C');
        $testedInstance->getSdkClientForClass('NotMapped\Entity\C');
    }

    /**
     * @param array<string> $nameList
     *
     * @return array<SdkClient&MockObject>
     */
    private function createSdkClientList(array $nameList): array
    {
        $sdkClientList = [];

        foreach ($nameList as $name) {
            $sdkClientList[$name] = $this->createMock(SdkClient::class);
        }

        return $sdkClientList;
    }
}
