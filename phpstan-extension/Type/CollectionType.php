<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\PHPStan\Type;

use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class CollectionType extends IntersectionType
{
    /** @var ObjectType */
    private $keyType;

    public function __construct(Type $itemType)
    {
        $this->keyType = new ObjectType('Mapado\RestClientSdk\Collection\Collection');

        parent::__construct([
            $this->keyType,
            new IterableType(new MixedType(), $itemType),
        ]);
    }
}
