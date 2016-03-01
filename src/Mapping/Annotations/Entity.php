<?php

namespace Mapado\RestClientSdk\Mapping\Annotations;

/**
 * Class Entity
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
     * @access public
     *
     * @Required
     */
    public $key;

    /**
     * repository
     *
     * @var string
     * @access public
     *
     */
    public $repository;
}
