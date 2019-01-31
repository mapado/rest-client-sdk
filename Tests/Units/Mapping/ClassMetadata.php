<?php

namespace Mapado\RestClientSdk\Tests\Units\Mapping;

use atoum;
use Mapado\RestClientSdk\Exception\MissingIdentifierException;
use Mapado\RestClientSdk\Exception\MoreThanOneIdentifierException;
use Mapado\RestClientSdk\Mapping\Attribute;

class ClassMetadata extends atoum
{
    public function testSetAttributeListWithoutId()
    {
        $this
            ->given($testedInstance = $this->newTestedInstance('key', 'Model', 'ModelRepository'))
            ->and($testedInstance->setAttributeList([
                new Attribute('not an id'),
            ]))
            ->exception(function () use ($testedInstance) {
                $testedInstance->getIdentifierAttribute();
            })
                ->isInstanceOf(MissingIdentifierException::class)
        ;
    }

    public function testThrowExceptionIfMoreThanOneIdentifierAttribute()
    {
        $this
            ->given($testedInstance = $this->newTestedInstance('key', 'Model', 'ModelRepository'))
            ->exception(function () use ($testedInstance) {
                $testedInstance->setAttributeList([
                    new Attribute('a first id', null, null, true),
                    new Attribute('a second id', null, null, true),
                ]);
            })
                ->isInstanceOf(MoreThanOneIdentifierException::class)
                    ->hasMessage('Class metadata for model "Model" already has an identifier named "a first id". Only one identifier is allowed.')
        ;
    }
}
