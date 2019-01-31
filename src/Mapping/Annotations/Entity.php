<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Annotations;

/**
 * Class Entity
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Entity
{
    /**
     * key
     *
     * @var string
     *
     * @Required
     */
    public $key;

    /**
     * repository
     *
     * @var string
     */
    public $repository;
}
