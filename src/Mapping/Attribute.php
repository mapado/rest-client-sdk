<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class Attribute
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Attribute
{
    private $name;

    private $type;

    private $isIdentifier;

    /**
     * __construct
     *
     * @param string $name
     * @param string $type
     * @param boolean $isIdentifier
     * @access public
     */
    public function __construct($name, $type = 'string', $isIdentifier = false)
    {
        if (empty($name)) {
            throw \InvalidArgumentException('attribute name must be set');
        }

        $this->name = $name;
        $this->type = $type;
        $this->isIdentifier = $isIdentifier;
    }

    /**
     * Getter for name
     *
     * return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for name
     *
     * @param string $name
     * @return Attribute
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for type
     *
     * return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for type
     *
     * @param string $type
     * @return Attribute
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Getter for isIdentifier
     *
     * return boolean
     */
    public function isIdentifier()
    {
        return $this->isIdentifier;
    }

    /**
     * Setter for isIdentifier
     *
     * @param boolean $isIdentifier
     * @return Attribute
     */
    public function setIsIdentifier($isIdentifier)
    {
        $this->isIdentifier = $isIdentifier;
        return $this;
    }
}
