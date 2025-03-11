<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Attributes;

abstract class Relation
{
    /**
     * @param class-string $targetEntity
     */
    public function __construct(
        public readonly string $name,
        public readonly string $targetEntity,
    ) {
    }
}
