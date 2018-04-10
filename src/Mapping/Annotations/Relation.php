<?php

namespace Mapado\RestClientSdk\Mapping\Annotations;

/**
 * Class Relation
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
abstract class Relation
{
    /**
     * name
     *
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * targetEntity
     *
     * @var string
     *
     * @Required
     */
    public $targetEntity;
}
