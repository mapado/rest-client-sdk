<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity extends AbstractClassAttribute
{
    /**
     * @param class-string|null $repository
     */
    public function __construct(
        public readonly string $key,
        public ?string $repository = null
    ) {
    }
}