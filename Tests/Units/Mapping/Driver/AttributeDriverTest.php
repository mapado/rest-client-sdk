<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Mapping\Driver;

use Mapado\RestClientSdk\Mapping\Driver\AttributeDriver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AttributeDriver
 */
class AttributeDriverTest extends TestCase
{
    public function testClassWithoutEntityAttribute(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadClassname('Mapado\RestClientSdk\Tests\Model\JsonLd\Client');

        $this->assertNotEmpty($mapping);
    }

    public function testLoadDirectory(): void
    {
        $testedInstance = new AttributeDriver($this->getCacheDir(), true);

        $mapping = $testedInstance->loadDirectory(__DIR__ . '/../../../Model/JsonLd');
        $this->assertCount(4, $mapping);
    }

    /**
     * getCacheDir
     *
     * @return string
     */
    private function getCacheDir()
    {
        return __DIR__ . '/../../../cache/';
    }
}
