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
     * client
     *
     * @var string
     * @access public
     *
     * @Required
     */
    public $client;
}
