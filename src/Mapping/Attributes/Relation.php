<?php

namespace Mapado\RestClientSdk\Mapping\Attributes;

abstract class Relation extends AbstractPropertyAttribute
{
    public function __construct(public readonly string $name, public readonly string $targetEntity)
    {
    }
}