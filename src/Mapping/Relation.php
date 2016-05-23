<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class Relation
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Relation
{
    const MANY_TO_ONE = 'ManyToOne';
    const ONE_TO_MANY = 'OneToMany';

    /**
     * key
     *
     * @var string
     * @access private
     */
    private $key;

    /**
     * type
     *
     * @var string
     * @access private
     */
    private $type;

    /**
     * targetEntity
     *
     * @var string
     * @access private
     */
    private $targetEntity;

    /**
     * __construct
     *
     * @param string $key
     * @param string $type
     * @access public
     */
    public function __construct($key, $type, $targetEntity)
    {
        $this->key = $key;
        $this->type = $type;
        $this->targetEntity = $targetEntity;
    }

    /**
     * Getter for key
     *
     * return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Setter for key
     *
     * @param string $key
     * @return Relation
     */
    public function setKey($key)
    {
        $this->key = $key;
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
     * @return Relation
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * isOneToMany
     *
     * @access public
     * @return boolean
     */
    public function isOneToMany()
    {
        return $this->getType() == self::ONE_TO_MANY;
    }

    /**
     * isManyToOne
     *
     * @access public
     * @return boolean
     */
    public function isManyToOne()
    {
        return $this->getType() == self::MANY_TO_ONE;
    }

    /**
     * Getter for targetEntity
     *
     * return string
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * Setter for targetEntity
     *
     * @param string $targetEntity
     * @return Relation
     */
    public function setTargetEntity($targetEntity)
    {
        $this->targetEntity = $targetEntity;
        return $this;
    }
}
