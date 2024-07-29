<?php

namespace Mapado\RestClientSdk\Mapping\Attributes;

use Mapado\RestClientSdk\Mapping\Relation as RelationMapping;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class OneToMany extends Relation
{
    public string $type = RelationMapping::ONE_TO_MANY;
}