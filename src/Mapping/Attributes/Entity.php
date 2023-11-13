<?php

namespace Mapado\RestClientSdk\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity extends AbstractClassAttribute
{
    public function __construct(
        public readonly string $key,
        public readonly ?string $repository = null
    )
    {
    }
}