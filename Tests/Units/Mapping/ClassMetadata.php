<?php

namespace Mapado\RestClientSdk\Tests\Units\Mapping;

use atoum;
use Mapado\RestClientSdk\Exception\MissingIdentifierException;
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
}
