<?php

namespace Mapado\RestClientSdk\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
abstract class Relation extends AbstractPropertyAttribute
{
    public function __construct(public readonly string $name, public readonly string $targetEntity)
    {
    }
}