<?php

namespace Mapado\RestClientSdk\Mapping;

use Mapado\RestClientSdk\Exception\MissingIdentifierException;
use Mapado\RestClientSdk\Exception\MoreThanOneIdentifierException;

/**
 * Class ClassMetadata
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ClassMetadata
{
    /**
     * Model name (entity class with full namespace, ie: "Foo\Entity\Article").
     *
     * @var string
     */
    private $modelName;

    /**
     * Model key, used as path prefix for API calls.
     *
     * @var string
     */
    private $key;

    /**
     * Repository name (repository class with full namespace, ie: "Foo\Repository\ArticleRepository").
     *
     * @var string
     */
    private $repositoryName;

    /**
     * attributeList
     *
     * @var Attribute[]
     */
    private $attributeList;

    /**
     * relationList
     *
     * @var Relation[]
     */
    private $relationList;

    /**
     * identifierAttribute
     *
     * @var ?Attribute
     */
    private $identifierAttribute;

    /**
     * Constructor.
     *
     * @param string $key
     * @param string $modelName
     * @param string $repositoryName
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
     *
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
     *
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
     *
     * @return ?Attribute
     */
    public function getAttribute($name)
    {
        return isset($this->attributeList[$name])
            ? $this->attributeList[$name]
            : null;
    }

    public function hasIdentifierAttribute(): bool
    {
        return (bool) $this->identifierAttribute;
    }

    /**
     * getIdentifierAttribute
     *
     * @return Attribute
     */
    public function getIdentifierAttribute()
    {
        if (!$this->identifierAttribute) {
            throw new MissingIdentifierException(
                sprintf(
                    'Ressource "%s" does not contains an identifier. You can not call %s. You may want to call `hasIdentifierAttribute` before.',
                    $this->modelName,
                    __METHOD__
                )
            );
        }

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
     *
     * @return ClassMetadata
     */
    public function setAttributeList($attributeList)
    {
        $this->attributeList = [];

        foreach ($attributeList as $attribute) {
            $this->attributeList[$attribute->getSerializedKey()] = $attribute;

            if ($attribute->isIdentifier()) {
                if ($this->identifierAttribute) {
                    throw new MoreThanOneIdentifierException(
                        sprintf(
                            'Class metadata for model "%s" already has an identifier named "%s". Only one identifier is allowed.',
                            $this->modelName,
                            $this->identifierAttribute->getSerializedKey()
                        )
                    );
                }

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
     *
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
     *
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
     *
     * @return ClassMetadata
     */
    public function setRepositoryName($repositoryName)
    {
        $this->repositoryName = $repositoryName;

        return $this;
    }

    public function getIdGetter()
    {
        return 'get' . ucfirst($this->getIdKey());
    }

    public function getIdSerializeKey()
    {
        return $this->getIdentifierAttribute()->getSerializedKey();
    }

    /**
     * return default serialize model with null value or empty array on relations
     *
     * @return array
     */
    public function getDefaultSerializedModel()
    {
        $out = [];
        $attributeList = $this->getAttributeList();
        if ($attributeList) {
            foreach ($attributeList as $attribute) {
                $out[$attribute->getSerializedKey()] = null;
            }
        }

        $relationList = $this->getRelationList();
        if ($relationList) {
            foreach ($relationList as $relation) {
                if ($relation->isOneToMany()) {
                    $out[$relation->getSerializedKey()] = [];
                }
            }
        }

        return $out;
    }

    private function getIdKey()
    {
        return $this->getIdentifierAttribute()->getAttributeName();
    }
}
