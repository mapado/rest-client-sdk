<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping;

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
            if (array_key_exists($key, $oldSerializedModel)) {
                if (is_array($value)) {
                    $currentClassMetadata = $classMetadata->getRelation($key) ? $this->mapping->getClassMetadata($classMetadata->getRelation($key)->getTargetEntity()) : null;
                    $idSerializedKey = $currentClassMetadata ? $currentClassMetadata->getIdSerializeKey() : null;
                    $recursiveDiff = $this->getDirtyFields($value, $oldSerializedModel[$key], $currentClassMetadata);
                    if (count($recursiveDiff)) {
                        $dirtyFields[$key] = $this->addIdentifiers($value, $recursiveDiff, $idSerializedKey);

                        // if theres only ids not objects, keep them
                        foreach ($value as $valueKey => $valueId) {
                            if (is_string($valueId) && is_int($valueKey)) {
                                $dirtyFields[$key][$valueKey] = $valueId;
                            }
                        }
                    } elseif (count($value) != count($oldSerializedModel[$key])) {
                        // get all objects ids of new array
                        $dirtyFields[$key] = $this->addIdentifiers($value, [], $idSerializedKey);
                    }
                } else {
                    if ($value != $oldSerializedModel[$key]) {
                        $dirtyFields[$key] = $value;
                    }
                }
            } else {
                $dirtyFields[$key] = $value;
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
}
