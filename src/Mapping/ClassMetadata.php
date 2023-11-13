<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping;

use Mapado\RestClientSdk\EntityRepository;
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
     * @var class-string
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
     * @var array<Attribute>
     */
    private $attributeList;

    /**
     * relationList
     *
     * @var array<Relation>
     */
    private $relationList;

    /**
     * identifierAttribute
     *
     * @var ?Attribute
     */
    private $identifierAttribute;

    /**
     * @param class-string $modelName
     */
    public function __construct(
        string $key,
        string $modelName,
        ?string $repositoryName = null
    ) {
        $this->key = $key;
        $this->modelName = $modelName;
        $this->repositoryName = $repositoryName ?? EntityRepository::class;
        $this->attributeList = [];
        $this->relationList = [];
    }

    /**
     * @return class-string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param class-string $modelName
     * @return $this
     */
    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getAttribute(string $name): ?Attribute
    {
        return $this->attributeList[$name] ?? null;
    }

    public function hasIdentifierAttribute(): bool
    {
        return (bool) $this->identifierAttribute;
    }

    /**
     * @throws MissingIdentifierException
     */
    public function getIdentifierAttribute(): Attribute
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
     * @return array<Attribute>
     */
    public function getAttributeList(): array
    {
        return $this->attributeList;
    }

    /**
     * Setter for attributeList
     *
     * @param  iterable<Attribute> $attributeList
     */
    public function setAttributeList($attributeList): self
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
     * @return array<Relation>
     */
    public function getRelationList(): array
    {
        return $this->relationList;
    }

    /**
     * Setter for relationList
     *
     * @param array<Relation> $relationList
     */
    public function setRelationList($relationList): self
    {
        $this->relationList = $relationList;

        return $this;
    }

    public function getRelation(string $key): ?Relation
    {
        if (!empty($this->relationList)) {
            foreach ($this->relationList as $relation) {
                if ($relation->getSerializedKey() == $key) {
                    return $relation;
                }
            }
        }

        return null;
    }

    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    public function setRepositoryName(string $repositoryName): self
    {
        $this->repositoryName = $repositoryName;

        return $this;
    }

    public function getIdGetter(): string
    {
        return 'get' . ucfirst($this->getIdKey());
    }

    public function getIdSerializeKey(): string
    {
        return $this->getIdentifierAttribute()->getSerializedKey();
    }

    /**
     * return default serialize model with null value or empty array on relations
     *
     * @return array<string, array|null>
     */
    public function getDefaultSerializedModel(): array
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

    private function getIdKey(): string
    {
        return $this->getIdentifierAttribute()->getAttributeName();
    }
}
