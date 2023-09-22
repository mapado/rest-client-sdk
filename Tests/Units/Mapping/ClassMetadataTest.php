<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Units\Mapping;

use PHPUnit\Framework\TestCase;
use Mapado\RestClientSdk\Exception\MissingIdentifierException;
use Mapado\RestClientSdk\Exception\MoreThanOneIdentifierException;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * @covers ClassMetadata
 */
class ClassMetadataTest extends TestCase
{
    public function testSetAttributeListWithoutId()
    {
        $testedInstance = new ClassMetadata('key', 'Model', 'ModelRepository');
        $testedInstance->setAttributeList([
            new Attribute('not an id'),
        ]);

        $this->expectException(MissingIdentifierException::class);
        $testedInstance->getIdentifierAttribute();
    }

    public function testThrowExceptionIfMoreThanOneIdentifierAttribute()
    {
        $testedInstance = new ClassMetadata('key', 'Model', 'ModelRepository');

        $this->expectException(MoreThanOneIdentifierException::class);
        $this->expectExceptionMessage('Class metadata for model "Model" already has an identifier named "a first id". Only one identifier is allowed.');

        $testedInstance->setAttributeList([
            new Attribute('a first id', null, null, true),
            new Attribute('a second id', null, null, true),
        ]);
    }
}