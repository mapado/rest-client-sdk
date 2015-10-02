<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class ClassMetadata
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ClassMetadata
{
    /**
     * clientName
     *
     * @var string
     * @access private
     */
    private $clientName;

    /**
     * modelName
     *
     * @var string
     * @access private
     */
    private $modelName;

    /**
     * key
     *
     * @var string
     * @access private
     */
    private $key;

    /**
     * attributeList
     *
     * @var string
     * @access private
     */
    private $attributeList;

    /**
     * relationList
     *
     * @var string
     * @access private
     */
    private $relationList;

    /**
     * __construct
     *
     * @param string $key
     * @param string $modelName
     * @param string $clientName
     * @access public
     */
    public function __construct($key, $modelName, $clientName)
    {
        $this->key = $key;
        $this->modelName = $modelName;
        $this->clientName = $clientName;
    }

    /**
     * Getter for clientName
     *
     * return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * Setter for clientName
     *
     * @param string $clientName
     * @return ClassMetadata
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;
        return $this;
    }

    /**
     * Getter for modelName
     *
     * return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Setter for modelName
     *
     * @param string $modelName
     * @return ClassMetadata
     */
    public function setModelName($modelName)
    {
        $this->modelName = $modelName;
        return $this;
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
     * @return ClassMetadata
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * getAttribute
     *
     * @param string $name
     * @access public
     * @return Attribute
     */
    public function getAttribute($name)
    {
        foreach ($this->attributeList as $attribute) {
            if ($attribute->getName() == $name) {
                return $attribute;
            }
        }
    }

    /**
     * Getter for attributeList
     *
     * return array
     */
    public function getAttributeList()
    {
        return $this->attributeList;
    }

    /**
     * Setter for attributeList
     *
     * @param array<Attribute> $attributeList
     * @return ClassMetadata
     */
    public function setAttributeList($attributeList)
    {
        $this->attributeList = $attributeList;
        return $this;
    }

    /**
     * Getter for relationList
     *
     * return array
     */
    public function getRelationList()
    {
        return $this->relationList;
    }

    /**
     * Setter for relationList
     *
     * @param array $relationList
     * @return ClassMetadata
     */
    public function setRelationList($relationList)
    {
        $this->relationList = $relationList;
        return $this;
    }

    /**
     * getRelation
     *
     * @param string $key
     * @access public
     * @return Relation|null
     */
    public function getRelation($key)
    {
        if (!empty($this->relationList)) {
            foreach ($this->relationList as $relation) {
                if ($relation->getKey() == $key) {
                    return $relation;
                }
            }
        }
    }
}
