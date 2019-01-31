<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Annotations;

use Mapado\RestClientSdk\Mapping\Relation as RelationMapping;

/**
 * Class OneToMany
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class OneToMany extends Relation
{
    /**
     * @var string
     */
    public $type = RelationMapping::ONE_TO_MANY;
}
