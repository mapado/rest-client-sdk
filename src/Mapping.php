<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\MappingException;

/**
 * Class Mapping
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Mapping
{
    private $idPrefix;

    private $idPrefixLength;

    private $mapping = [];

    /**
     * __construct
     *
     * @param string $idPrefix
     * @access public
     */
    public function __construct($idPrefix = '')
    {
        $this->idPrefix = $idPrefix;
        $this->idPrefixLength = strlen($idPrefix);
    }

    /**
     * getIdPrefix
     *
     * @access public
     * @return string
     */
    public function getIdPrefix()
    {
        return $this->idPrefix;
    }

    /**
     * setMapping
     *
     * @param array $mapping
     * @access public
     * @return Mapping
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * return a model class name for a given key
     *
     * @param string $key
     * @access public
     * @return string
     */
    public function getModelName($key)
    {
        $this->checkMappingExistence($key, 'modelName');

        return $this->getClassMetadataByKey($key)->getModelName();
    }

    /**
     * return the list of mapping keys
     *
     * @access public
     * @return array
     */
    public function getMappingKeys()
    {
        return array_map(
            function ($mapping) {
                return $mapping->getKey();
            },
            $this->mapping
        );
    }

    /**
     * get the key from an id (path)
     *
     * @param string $id
     * @access public
     * @return void
     */
    public function getKeyFromId($id)
    {
        $id = $this->removePrefix($id);
        $key = substr($id, 1, strrpos($id, '/') - 1);
        $this->checkMappingExistence($key);

        return $key;
    }

    /**
     * getKeyFromModel
     *
     * @param string $modelName model name
     * @access public
     * @return void
     */
    public function getKeyFromModel($modelName)
    {
        foreach ($this->mapping as $mapping) {
            if ($modelName === $mapping->getModelName()) {
                return $mapping->getKey();
            }
        }

        throw new MappingException('Model name ' . $modelName . ' not found in mapping');
    }

    /**
     * getClassMetadata for model name
     *
     * @param string $modelName
     * @access public
     * @return ClassMetadata
     */
    public function getClassMetadata($modelName)
    {
        foreach ($this->mapping as $mapping) {
            if ($modelName === $mapping->getModelName()) {
                return $mapping;
            }
        }

        throw new MappingException($modelName . ' model is not mapped');
    }

    /**
     * hasClassMetadata
     *
     * @param string $modelName
     * @access public
     * @return boolean
     */
    public function hasClassMetadata($modelName)
    {
        foreach ($this->mapping as $mapping) {
            if ($modelName === $mapping->getModelName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * getMappingByKey
     *
     * @param string $key
     * @access public
     * @return ClassMetadata
     */
    public function getClassMetadataByKey($key)
    {
        foreach ($this->mapping as $mapping) {
            if ($key === $mapping->getKey()) {
                return $mapping;
            }
        }
    }

    /**
     * checkMappingExistence
     *
     * @param string $key
     * @param string $subKey
     * @access private
     * @return void
     */
    private function checkMappingExistence($key, $subKey = null)
    {
        if (empty($key)) {
            throw new MappingException('key is not set');
        }

        $metadata = $this->getClassMetadataByKey($key);
        if (!$metadata) {
            throw new MappingException($key . ' key is not mapped');
        }

        if ($subKey) {
            $methodName = 'get' . ucfirst($subKey);
            if (!$metadata->$methodName()) {
                throw new MappingException($key . ' key is mapped but no ' . $subKey . ' found');
            }
        }
    }

    /**
     * removePrefix
     *
     * @param mixed $value
     * @access private
     * @return string
     */
    private function removePrefix($value)
    {
        if ($this->idPrefix && strpos($value, $this->idPrefix) !== false) {
            return substr($value, $this->idPrefixLength);
        }

        return $value;
    }
}
