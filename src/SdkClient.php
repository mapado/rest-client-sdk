<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Model\ModelHydrator;
use Mapado\RestClientSdk\Model\Serializer;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * Sdk Client
 */
class SdkClient
{
    protected $restClient;

    private $mapping;

    private $serializer;

    private $modelHydrator;

    private $repositoryList = [];

    /**
     * Constructor
     * @param ClientInterface $restClient
     */
    public function __construct(RestClient $restClient, Mapping $mapping, Serializer $serializer = null)
    {
        $this->restClient = $restClient;
        $this->mapping = $mapping;
        if (!$serializer) {
            $serializer = new Serializer($this->mapping);
        }
        $this->serializer = $serializer;
        $this->serializer->setSdk($this);

        $this->modelHydrator = new ModelHydrator($this);
    }

    /**
     * getRepository
     *
     * @param string $modelName
     * @access public
     * @return EntityRepository
     */
    public function getRepository($modelName)
    {
        if (!isset($this->repositoryList[$modelName])) {
            $metadata = $this->mapping->getClassMetadata($modelName);
            $key = $metadata->getKey();
            $repositoryName = $metadata->getRepositoryName() ?: '\Mapado\RestClientSdk\EntityRepository';
            $this->repositoryList[$modelName] = new $repositoryName($this, $this->restClient, $modelName);
        }

        return $this->repositoryList[$modelName];
    }

    /**
     * getRestClient
     *
     * @access public
     * @return RestClient
     */
    public function getRestClient()
    {
        return $this->restClient;
    }

    /**
     * getMapping
     *
     * @access public
     * @return Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * getSerializer
     *
     * @access public
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * getModelHydrator
     *
     * @access public
     * @return ModelHydrator
     */
    public function getModelHydrator()
    {
        return $this->modelHydrator;
    }

    /**
     * createProxy
     *
     * @param string $id
     * @access public
     * @return object
     */
    public function createProxy($id)
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);

        $modelName = $classMetadata->getModelName();

        $sdk = $this;

        $factory     = new LazyLoadingGhostFactory();
        $initializer = function (
            LazyLoadingInterface &$proxy,
            $method,
            array $parameters,
            & $initializer
        ) use (
            $sdk,
            $classMetadata,
            $id
        ) {
            if ($method !== 'getId' && $method !== 'setId' && $method !== 'jsonSerialize') {
                $initializer   = null; // disable initialization

                // load data and modify the object here
                if ($id) {
                    $repository = $sdk->getRepository($classMetadata->getModelName());
                    $model = $repository->find($id);

                    $attributeList = $classMetadata->getAttributeList();

                    foreach ($attributeList as $attribute) {
                        $getter = 'get' . ucfirst($attribute->getName());
                        $setter = 'set' . ucfirst($attribute->getName());
                        $value = $model->$getter();
                        $proxy->$setter($value);
                    }
                }

                return true; // confirm that initialization occurred correctly
            }
        };

        $instance = $factory->createProxy($modelName, $initializer);
        $instance->setId($id);

        return $instance;
    }
}
