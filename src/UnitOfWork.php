<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * UnitOfWork
 */
class UnitOfWork
{
    /**
     * mapping
     *
     * @var Mapping
     */
    private $mapping;

    /**
     * storage for every entity retrieved
     *
     * @var array<string, object>
     */
    private $storage;

    /**
     * Constructor.
     */
    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
        $this->storage = [];
    }

    /**
     * return the new serialized model with only needed fields to update
     */
    public function getDirtyData(
        array $newSerializedModel,
        array $oldSerializedModel,
        ClassMetadata $classMetadata
    ): array {
        return $this->getDirtyFields(
            $newSerializedModel,
            $oldSerializedModel,
            $classMetadata
        );
    }

    /**
     * Register a net entity in the UnitOfWork storage
     */
    public function registerClean(string $id, object $entity): self
    {
        $entityStored = clone $entity;
        $this->storage[$id] = $entityStored;

        return $this;
    }

    public function getDirtyEntity(string $id): ?object
    {
        return $this->storage[$id] ?? null;
    }

    public function clear(string $id): self
    {
        unset($this->storage[$id]);

        return $this;
    }

    /**
     * Compare serialize object and returns only modified fields
     */
    private function getDirtyFields(
        array $newSerializedModel,
        array $oldSerializedModel,
        ClassMetadata $classMetadata
    ): array {
        $dirtyFields = [];

        foreach ($newSerializedModel as $key => $value) {
            if (!array_key_exists($key, $oldSerializedModel)) {
                // a new key has been found, add it to the dirtyFields
                $dirtyFields[$key] = $value;
                continue;
            }

            $oldValue = $oldSerializedModel[$key];

            $currentRelation = $classMetadata->getRelation($key);

            if (!$currentRelation) {
                if (
                    (is_array($value) &&
                        !ArrayHelper::arraySame($value, $oldValue ?: [])) ||
                    $value !== $oldValue
                ) {
                    $dirtyFields[$key] = $value;
                }
                continue;
            }

            $currentClassMetadata = $this->mapping->getClassMetadata(
                $currentRelation->getTargetEntity()
            );

            $idSerializedKey = $currentClassMetadata->getIdSerializeKey();

            if (Relation::MANY_TO_ONE === $currentRelation->getType()) {
                if ($value !== $oldValue) {
                    if (is_string($value) || is_string($oldValue)) {
                        $dirtyFields[$key] = $value;
                    } else {
                        $recursiveDiff = $this->getDirtyFields(
                            $value,
                            $oldValue,
                            $currentClassMetadata
                        );

                        if (!empty($recursiveDiff)) {
                            $recursiveDiff[
                                $idSerializedKey
                            ] = self::getEntityId($value, $idSerializedKey);
                            $dirtyFields[$key] = $recursiveDiff;
                        }
                    }
                }

                continue;
            }

            // ONE_TO_MANY relation
            if (count($value ?? []) !== count($oldValue ?? [])) {
                // get all objects ids of new array
                $dirtyFields[$key] = $this->addIdentifiers(
                    $value,
                    [],
                    $idSerializedKey
                );
            }

            if (!empty($value)) {
                foreach ($value as $relationKey => $relationValue) {
                    $oldRelationValue = $this->findOldRelation(
                        $relationValue,
                        $oldValue,
                        $currentClassMetadata
                    );

                    if ($relationValue !== $oldRelationValue) {
                        if (
                            is_string($relationValue) ||
                            is_string($oldRelationValue)
                        ) {
                            $dirtyFields[$key][$relationKey] = $relationValue;
                        } else {
                            $recursiveDiff = $this->getDirtyFields(
                                $relationValue,
                                $oldRelationValue,
                                $currentClassMetadata
                            );

                            if (!empty($recursiveDiff)) {
                                $idSerializedKey = $currentClassMetadata->getIdSerializeKey();

                                $entityId = self::getEntityId(
                                    $relationValue,
                                    $idSerializedKey
                                );
                                if (null !== $entityId) {
                                    $recursiveDiff[
                                        $idSerializedKey
                                    ] = $entityId;
                                }
                                $dirtyFields[$key][
                                    $relationKey
                                ] = $recursiveDiff;
                            }
                        }
                    }
                }
            }
        }

        return $dirtyFields;
    }

    /**
     * add defined identifiers to given model
     *
     * @param array $newSerializedModel
     * @param ?string $idSerializedKey
     */
    private function addIdentifiers(
        array $newSerializedModel,
        array $dirtyFields,
        $idSerializedKey = null
    ): array {
        foreach ($newSerializedModel as $key => $value) {
            if ($idSerializedKey && isset($value[$idSerializedKey])) {
                $dirtyFields[$key][$idSerializedKey] = $value[$idSerializedKey];
            } elseif (is_string($value) && is_int($key)) {
                $dirtyFields[$key] = $value;
            }
        }

        return $dirtyFields;
    }

    /**
     * @param string|array $relationValue
     *
     * @return array|string
     */
    private function findOldRelation(
        $relationValue,
        array $oldValue,
        ClassMetadata $classMetadata
    ) {
        $idSerializedKey = $classMetadata->getIdSerializeKey();

        $relationValueId = self::getEntityId($relationValue, $idSerializedKey);

        foreach ($oldValue as $oldRelationValue) {
            $oldRelationValueId = self::getEntityId(
                $oldRelationValue,
                $idSerializedKey
            );

            if ($relationValueId === $oldRelationValueId) {
                return $oldRelationValue;
            }
        }

        return $classMetadata->getDefaultSerializedModel();
    }

    /**
     * get entity id from string or array
     *
     * @param string|array $stringOrEntity
     *
     * @return ?mixed
     */
    private static function getEntityId(
        $stringOrEntity,
        string $idSerializedKey
    ) {
        if (!is_array($stringOrEntity)) {
            return $stringOrEntity;
        }

        return $stringOrEntity[$idSerializedKey] ?? null;
    }
}
