<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class ClassMetadata
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ClassMetadata
{
    /**
     * Model name (entity class with full namespace, ie: "Foo\Entity\Article").
     *
     * @var string
     * @access private
     */
    private $modelName;

    /**
     * Model key, used as path prefix for API calls.
     *
     * @var string
     * @access private
     */
    private $key;

    /**
     * Repository name (repository class with full namespace, ie: "Foo\Repository\ArticleRepository").
     *
     * @var string
     * @access private
     */
    private $repositoryName;

    /**
     * attributeList
     *
     * @var Attribute[]
     * @access private
     */
    private $attributeList;

    /**
     * relationList
     *
     * @var Relation[]
     * @access private
     */
    private $relationList;

    /**
     * identifierAttribute
     *
     * @var Attribute
     * @access private
     */
    private $identifierAttribute;

    /**
     * Constructor.
     *
     * @param string $key
     * @param string $modelName
     * @param string $repositoryName
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
     * @return string
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
     * @return string
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
        return isset($this->attributeList[$name]) ? $this->attributeList[$name] : null;
    }

    /**
     * getIdentifierAttribute
     *
     * @access public
     * @return Attribute
     */
    public function getIdentifierAttribute()
    {
        return $this->identifierAttribute;
    }

    /**
     * Getter for attributeList
     *
     * @return Attribute[]
     */
    public function getAttributeList()
    {
        return $this->attributeList;
    }

    /**
     * Setter for attributeList
     *
     * @param Attribute[] $attributeList
     * @return ClassMetadata
     */
    public function setAttributeList($attributeList)
    {
        $this->attributeList = [];
        foreach ($attributeList as $attribute) {
            $this->attributeList[$attribute->getSerializedKey()] = $attribute;

            if ($attribute->isIdentifier()) {
                $this->identifierAttribute = $attribute;
            }
        }
        return $this;
    }

    /**
     * Getter for relationList
     *
     * @return Relation[]
     */
    public function getRelationList()
    {
        return $this->relationList;
    }

    /**
     * Setter for relationList
     *
     * @param Relation[] $relationList
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
     * @return string
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
