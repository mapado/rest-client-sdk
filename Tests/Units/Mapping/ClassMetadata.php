<?php

namespace Mapado\RestClientSdk\Tests\Units\Mapping;

use atoum;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * Class ClassMetadata
 */
class ClassMetadata extends atoum
{
    /**
     * testClassWithoutEntityAnnotation
     *
     * @access public
     * @return void
     */
    public function testGetModelShortName()
    {
        $this
            ->given($this->newTestedInstance('bars', 'Foo\Entity\Bar', 'Foo\Repository\BarRepository'))
            ->then
                ->variable($this->testedInstance->getModelShortName())
                ->isEqualTo('Bar')
        ;
    }
}
