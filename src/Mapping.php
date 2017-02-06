<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * Class Mapping
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Mapping
{
    const DEFAULT_CONFIG = [
        'collectionKey' => 'hydra:member',
    ];

    /**
     * @var string
     */
    private $idPrefix;

    /**
     * @var int
     */
    private $idPrefixLength;

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * config
     *
     * @var array
     * @access private
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string $idPrefix
     * @access public
     */
    public function __construct($idPrefix = '', $config = [])
    {
        $this->idPrefix = $idPrefix;
        $this->idPrefixLength = strlen($idPrefix);
        $this->setConfig($config);
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
     * Getter for config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Setter for config
     *
     * @param array $config
     * @return Mapping
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge(
            self::DEFAULT_CONFIG,
            $config
        );

        return $this;
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
     * @return string
     */
    public function getKeyFromId($id)
    {
        $id = $this->removePrefix($id);

        $lastSeparator = strrpos($id, '/');
        $secondLast = strrpos($id, '/', $lastSeparator - strlen($id) - 1) + 1;

        $keyLength = abs($secondLast - $lastSeparator);
        $key = substr($id, $secondLast, $keyLength);
        $this->checkMappingExistence($key);

        return $key;
    }

    /**
     * getKeyFromModel
     *
     * @param string $modelName model name
     * @access public
     * @return string
     * @throws MappingException
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
     * @return ClassMetadata|null
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
     * @param string|null $subKey
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

        if (!empty($subKey)) {
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
