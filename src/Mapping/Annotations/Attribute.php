<?php

namespace Mapado\RestClientSdk\Mapping\Annotations;

/**
 * Class Attribute
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Attribute
{
    /**
     * type
     *
     * @var string
     *
     * @Required
     */
    public $type;

    /**
     * name
     *
     * @var string
     *
     * @Required
     */
    public $name;
}
