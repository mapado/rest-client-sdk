<?php

namespace Mapado\RestClientSdk\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Attribute extends AbstractPropertyAttribute
{
    public function __construct(public readonly string $name, public readonly string $type)
    {
    }
}