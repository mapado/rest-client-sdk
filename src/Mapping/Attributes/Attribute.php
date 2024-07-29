<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Attribute
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }
}
