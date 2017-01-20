<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class ClassMetadata
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ClassMetadata
{
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
     * repositoryName
     *
     * @var string
     * @access private
     */
    private $repositoryName;

    /**
     * attributeList
     *
     * @var array<Attribute>
     * @access private
     */
    private $attributeList;

    /**
     * relationList
     *
     * @var array<Relation>
     * @access private
     */
    private $relationList;

    /**
     * __construct
     *
     * @param string $key
     * @param string $modelName
     * @access public
     */
    public function __construct($key, $modelName, $repositoryName)
    {
        $this->key = $key;
        $this->modelName = $modelName;
        $this->repositoryName = $repositoryName;
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
            if ($attribute->getSerializedKey() == $name) {
                return $attribute;
            }
        }
    }

    /**
     * Getter for attributeList
     *
     * return array<Attribute>
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
     * return array<Relation>
     */
    public function getRelationList()
    {
        return $this->relationList;
    }

    /**
     * Setter for relationList
     *
     * @param array<Relation> $relationList
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
                if ($relation->getSerializedKey() == $key) {
                    return $relation;
                }
            }
        }
    }

    /**
     * Getter for repositoryName
     *
     * return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    /**
     * Setter for repositoryName
     *
     * @param string $repositoryName
     * @return ClassMetadata
     */
    public function setRepositoryName($repositoryName)
    {
        $this->repositoryName = $repositoryName;
        return $this;
    }
}
