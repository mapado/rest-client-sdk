<?php

namespace Mapado\RestClientSdk\Tests\Model\Issue75;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="tags")
 */
class Tag
{
    /**
     * @Rest\Attribute(name="name", type="string")
     */
    private $name;

    /**
     * Getter for name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for name
     *
     * @param string $name
     *
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
