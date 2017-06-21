<?php

namespace Mapado\RestClientSdk;

class UnitOfWork
{
    private $storage;

    public function __construct()
    {
        $this->storage = [];
    }

    private function arrayRecursiveDiff($newModel, $oldModel, $isInDepth = false)
    {
        $diff = [];
        $hasDiff = false;

        foreach ($newModel as $key => $value) {
            if (array_key_exists($key, $oldModel)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayRecursiveDiff($value, $oldModel[$key], true);
                    if (count($recursiveDiff)) {
                        $hasDiff = true;
                        $diff[$key] = $recursiveDiff;

                        //if theres only ids, keep them
                        foreach ($value as $valueKey => $valueId) {
                            if (is_string($valueId) && is_int($valueKey)) {
                                $diff[$key][$valueKey] = $valueId;
                            }
                        }
                    } elseif (count($value) != count($oldModel[$key])) {
                        $hasDiff = true;
                        //get all objects ids of new array
                        $diff[$key] = [];
                        $diff[$key] = $this->addIds($value, $diff[$key]);
                    }
                } else {
                    if ($value != $oldModel[$key]) {
                        $diff[$key] = $value;
                    }
                }
            } else {
                $diff[$key] = $value;
            }
        }

        if ($isInDepth && $hasDiff) {
            // in depth add ids of modified objects
            $diff = $this->addIds($newModel, $diff);
        }

        return $diff;
    }

    private function addIds($newModel, $diff)
    {
        foreach ($newModel as $key => $value) {
            if (isset($value['@id'])) {
                $diff[$key]['@id'] = $value['@id'];
            }
        }

        return $diff;
    }

    public function getObjectStorage()
    {
        return $this->storage;
    }

    public function getDirtyData($newModel, $oldModel)
    {
        return $this->arrayRecursiveDiff($newModel, $oldModel);
    }

    public function registerClean($id, $entity)
    {
        if ($entity) {
            $entityStored = clone $entity;
            $this->storage[$id] = $entityStored;
        }

        return $this;
    }

    public function getDirtyEntity($id)
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }
        return null;
    }

    public function clear($id)
    {
        unset($this->storage[$id]);
        return $this;
    }
}
