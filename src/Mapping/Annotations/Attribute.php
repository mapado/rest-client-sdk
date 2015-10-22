<?php

namespace Mapado\RestClientSdk\Mapping\Annotations;

/**
 * Class Attribute
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
     * @access public
     *
     * @Required
     */
    public $type;

    /**
     * name
     *
     * @var string
     * @access public
     *
     * @Required
     */
    public $name;
}
