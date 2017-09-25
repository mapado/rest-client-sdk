<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping;
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
     * @access private
     */
    private $mapping;

    /**
     * storage for every entity retrieved
     *
     * @var array
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
     * getDirtyData
     *
     * return the new serialized model with only needed fields to update
     *
     * @param array $newSerializedModel
     * @param array $oldSerializedModel
     * @param ClassMetadata $classMetadata
     * @access public
     * @return array
     */
    public function getDirtyData(array $newSerializedModel, array $oldSerializedModel, ClassMetadata $classMetadata)
    {
        return $this->getDirtyFields($newSerializedModel, $oldSerializedModel, $classMetadata);
    }

    /**
     * registerClean
     *
     * @param string $id
     * @param object $entity
     * @access public
     * @return UnitOfWork
     */
    public function registerClean($id, $entity)
    {
        if (is_object($entity)) {
            $entityStored = clone $entity;
            $this->storage[$id] = $entityStored;
        }

        return $this;
    }

    /**
     * getDirtyEntity
     *
     * @param string $id
     * @access public
     * @return mixed
     */
    public function getDirtyEntity($id)
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }
        return null;
    }

    /**
     * clear
     *
     * @param string $id
     * @access public
     * @return UnitOfWork
     */
    public function clear($id)
    {
        unset($this->storage[$id]);
        return $this;
    }

    /**
     * getDirtyFields
     *
     * compares serialize object and returns only modified fields
     *
     * @param array $newArrayModel
     * @param array $oldSerializedModel
     * @param ClassMetadata $classMetadata
     * @access private
     * @return array
     */
    private function getDirtyFields(array $newSerializedModel, array $oldSerializedModel, ClassMetadata $classMetadata = null)
    {
        $dirtyFields = [];

        foreach ($newSerializedModel as $key => $value) {
            if (!array_key_exists($key, $oldSerializedModel)) {
                // a new key has been found, add it to the dirtyFields
                $dirtyFields[$key] = $value;
                continue;
            }

            $oldValue = $oldSerializedModel[$key];

            $currentRelation = $classMetadata ? $classMetadata->getRelation($key) : null;

            if (!$currentRelation) {
                if (is_array($value) && !ArrayHelper::arraySame($value, $oldValue)
                    || $value != $oldValue
                ) {
                    $dirtyFields[$key] = $value;
                }
                continue;
            }

            $currentClassMetadata = $this->mapping->getClassMetadata($currentRelation->getTargetEntity());

            $idSerializedKey = $currentClassMetadata ? $currentClassMetadata->getIdSerializeKey() : null;

            if ($currentRelation->getType() === Relation::MANY_TO_ONE) {
                if ($value !== $oldValue) {
                    if (is_string($value) || is_string($oldValue)) {
                        $dirtyFields[$key] = $value;
                    } else {
                        $recursiveDiff = $this->getDirtyFields($value, $oldValue, $currentClassMetadata);

                        if (!empty($recursiveDiff)) {
                            $recursiveDiff[$idSerializedKey] = static::getEntityId($value, $idSerializedKey);
                            $dirtyFields[$key] = $recursiveDiff;
                        }
                    }
                }

                continue;
            }

            // ONE_TO_MANY relation

            if (count($value) != count($oldValue)) {
                // get all objects ids of new array
                $dirtyFields[$key] = $this->addIdentifiers($value, [], $idSerializedKey);
            }

            foreach ($value as $relationKey => $relationValue) {
                $oldRelationValue = $this->findOldRelation($relationValue, $oldValue, $currentClassMetadata);


                if ($relationValue !== $oldRelationValue) {
                    if (is_string($relationValue) || is_string($oldRelationValue)) {
                        $dirtyFields[$key][$relationKey] = $relationValue;
                    } else {
                        $recursiveDiff = $this->getDirtyFields($relationValue, $oldRelationValue, $currentClassMetadata);

                        if (!empty($recursiveDiff)) {
                            $idSerializedKey = $currentClassMetadata->getIdSerializeKey();

                            $recursiveDiff[$idSerializedKey] = static::getEntityId($relationValue, $idSerializedKey);
                            $dirtyFields[$key][$relationKey] = $recursiveDiff;
                        }
                    }
                }
            }
        }


        return $dirtyFields;
    }

    /**
     * addIdentifiers
     *
     * add defined identifiers to given model
     *
     * @param array $newSerializedModel
     * @param array $dirtyFields
     * @access private
     * @return array
     */
    private function addIdentifiers($newSerializedModel, $dirtyFields, $idSerializedKey = null)
    {
        foreach ($newSerializedModel as $key => $value) {
            if ($idSerializedKey && isset($value[$idSerializedKey])) {
                $dirtyFields[$key][$idSerializedKey] = $value[$idSerializedKey];
            } elseif (is_string($value) && is_int($key)) {
                $dirtyFields[$key] = $value;
            }
        }

        return $dirtyFields;
    }

    private function findOldRelation($relationValue, array $oldValue, ClassMetadata $classMetadata)
    {
        $idSerializedKey = $classMetadata->getIdSerializeKey();

        $relationValueId = static::getEntityId($relationValue, $idSerializedKey);

        foreach ($oldValue as $oldRelationValue) {
            $oldRelationValueId = static::getEntityId($oldRelationValue, $idSerializedKey);

            if ($relationValueId === $oldRelationValueId) {
                return $oldRelationValue;
            }
        }

        return [];
    }

    /**
     * get entity id from string or array
     * @param mixed $stringOrEntity
     * @param string $idSerializedKey
     */
    private static function getEntityId($stringOrEntity, $idSerializedKey)
    {
        if (!is_array($stringOrEntity)) {
            return $stringOrEntity;
        }

        return $stringOrEntity[$idSerializedKey];
    }
}
