<?php

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * Class Model
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Model
{
    /**
     * @Rest\Id
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * Getter for id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param string $id
     *
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
