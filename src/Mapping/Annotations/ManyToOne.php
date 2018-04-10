<?php

namespace Mapado\RestClientSdk\Mapping\Annotations;

use Mapado\RestClientSdk\Mapping\Relation as RelationMapping;

/**
 * Class ManyToOne
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class ManyToOne extends Relation
{
    /**
     * @var string
     */
    public $type = RelationMapping::MANY_TO_ONE;
}
