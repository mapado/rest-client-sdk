<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Attributes;

use Mapado\RestClientSdk\Mapping\Relation as RelationMapping;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ManyToOne extends Relation
{
    public string $type = RelationMapping::MANY_TO_ONE;
}
