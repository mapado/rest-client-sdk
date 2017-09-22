<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

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

            if (!is_array($value)) {
                // not an array, mean that the value is not a relation
                if ($value != $oldValue) {
                    // the value did change, an update is needed
                    $dirtyFields[$key] = $value;
                }
                continue;
            }

            $currentClassMetadata = $classMetadata && $classMetadata->getRelation($key)
                ? $this->mapping->getClassMetadata($classMetadata->getRelation($key)->getTargetEntity())
                : null
            ;

            // if (false && !$classMetadata) {
            //     // it mean that we are on a "real" array, not a relation,
            //     // so we need to check array equality

            //     $isMap = count(array_filter(array_keys($value), function ($key) {
            //         return !is_int($key);
            //     })) > 0;

            //     if ($isMap) {
            //         if (!ArrayHelper::arraySame($value, $oldValue)) {
            //             // the new array is not the same as the old array
            //             $dirtyFields[$key] = $value;
            //         }

            //         continue;
            //     }
            // }

            $idSerializedKey = $currentClassMetadata ? $currentClassMetadata->getIdSerializeKey() : null;
            $recursiveDiff = $this->getDirtyFields($value, $oldValue, $currentClassMetadata);

            if (count($recursiveDiff)) {
                $dirtyFields[$key] = $this->addIdentifiers($value, $recursiveDiff, $idSerializedKey);

                // if there is only ids not objects, keep them
                foreach ($value as $valueKey => $valueId) {
                    if (is_string($valueId) && is_int($valueKey)) {
                        $dirtyFields[$key][$valueKey] = $valueId;
                    }
                }
            } elseif (count($value) != count($oldValue)) {
                // get all objects ids of new array
                $dirtyFields[$key] = $this->addIdentifiers($value, [], $idSerializedKey);
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
