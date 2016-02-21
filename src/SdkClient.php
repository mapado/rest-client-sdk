<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Model\Serializer,
    Mapado\RestClientSdk\EntityRepository,
    Mapado\RestClientSdk\Client\Client;
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

    private $clientList = [];

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
    }

    /**
     * getClient
     *
     * @param string $clientName
     * @access public
     * @return AbstractClient
     */
    public function getClient($clientName = null)
    {
        if (!isset($this->clientList[$clientName])) {
            $classname = $this->mapping->getClientName($clientName);
            $client = new $classname($this);
            $this->clientList[$clientName] = $client;
        }

        return $this->clientList[$clientName];
    }
    
    public function getRepository($repositoryName) {
        $client = new \Mapado\RestClientSdk\Client\Client($this);
        $repository = new $repositoryName();
        $defaultRepository = new EntityRepository($client, $this->restClient, $repository);
        return $defaultRepository;
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
        $initializer = function (LazyLoadingInterface &$proxy, $method, array $parameters, & $initializer) use ($sdk, $classMetadata, $id) {
            if ($method !== 'getId' && $method !== 'setId' && $method !== 'jsonSerialize') {
                $initializer   = null; // disable initialization

                // load data and modify the object here
                if ($id) {
                    $key = $classMetadata->getKey();
                    $client = $sdk->getClient($key);
                    $model = $client->find($id);

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
