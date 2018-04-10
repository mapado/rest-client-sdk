<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * Class Mapping
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Mapping
{
    public const DEFAULT_CONFIG = [
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
     * @var ClassMetadata[]
     */
    private $classMetadataList = [];

    /**
     * config
     *
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string $idPrefix
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
     *
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
     * @param ClassMetadata[] $classMetadataList
     *
     * @return Mapping
     */
    public function setMapping(array $classMetadataList)
    {
        $this->classMetadataList = $classMetadataList;

        return $this;
    }

    /**
     * return a model class name for a given key
     *
     * @param string $key
     *
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
     * @return string[]
     */
    public function getMappingKeys()
    {
        return array_map(
            function (ClassMetadata $classMetadata) {
                return $classMetadata->getKey();
            },
            $this->classMetadataList
        );
    }

    /**
     * get the key from an id (path)
     *
     * @param string $id
     *
     * @return string
     */
    public function getKeyFromId($id)
    {
        $key = $this->parseKeyFromId($id);
        $this->checkMappingExistence($key);

        return $key;
    }

    /**
     * getKeyFromModel
     *
     * @param string $modelName model name
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getKeyFromModel($modelName)
    {
        foreach ($this->classMetadataList as $classMetadata) {
            if ($modelName === $classMetadata->getModelName()) {
                return $classMetadata->getKey();
            }
        }

        throw new MappingException('Model name ' . $modelName . ' not found in mapping');
    }

    /**
     * getClassMetadata for model name
     *
     * @param string $modelName
     *
     * @return ClassMetadata
     *
     * @throws MappingException
     */
    public function getClassMetadata($modelName)
    {
        foreach ($this->classMetadataList as $classMetadata) {
            if ($modelName === $classMetadata->getModelName()) {
                return $classMetadata;
            }
        }

        throw new MappingException($modelName . ' model is not mapped');
    }

    /**
     * getClassMetadata for id
     *
     * @param string $id
     *
     * @return ClassMetadata|null
     */
    public function tryGetClassMetadataById($id)
    {
        $key = $this->parseKeyFromId($id);

        foreach ($this->classMetadataList as $classMetadata) {
            if ($key === $classMetadata->getKey()) {
                return $classMetadata;
            }
        }
    }

    /**
     * hasClassMetadata
     *
     * @param string $modelName
     *
     * @return bool
     */
    public function hasClassMetadata($modelName)
    {
        foreach ($this->classMetadataList as $classMetadata) {
            if ($modelName === $classMetadata->getModelName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * getMappingByKey
     *
     * @param string $key
     *
     * @return ClassMetadata|null
     */
    public function getClassMetadataByKey($key)
    {
        foreach ($this->classMetadataList as $classMetadata) {
            if ($key === $classMetadata->getKey()) {
                return $classMetadata;
            }
        }
    }

    /**
     * Parse the key from an id (path)
     *
     * @param string $id
     *
     * @return string|null
     */
    private function parseKeyFromId($id)
    {
        $id = $this->removePrefix($id);

        $matches = [];
        if (1 === preg_match('|/([^/]+)/[^/]+$|', $id, $matches)) {
            return $matches[1];
        }
    }

    /**
     * checkMappingExistence
     *
     * @param string $key
     * @param string|null $subKey
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
     *
     * @return string
     */
    private function removePrefix($value)
    {
        if (($this->idPrefixLength > 0) && (0 === strpos($value, $this->idPrefix))) {
            return substr($value, $this->idPrefixLength);
        }

        return $value;
    }
}
