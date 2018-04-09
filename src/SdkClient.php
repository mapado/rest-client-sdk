<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Model\ModelHydrator;
use Mapado\RestClientSdk\Model\Serializer;
use Mapado\RestClientSdk\UnitOfWork;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Sdk Client
 */
class SdkClient
{
    /**
     * @var RestClient
     */
    protected $restClient;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ModelHydrator
     */
    private $modelHydrator;

    /**
     * @var array
     */
    private $repositoryList = [];

    /**
     * proxyManagerConfig
     *
     * @var Configuration
     * @access private
     */
    private $proxyManagerConfig;

    /**
     * Cache item pool.
     *
     * @var CacheItemPoolInterface
     * @access private
     */
    protected $cacheItemPool;

    /**
     * Cache prefix.
     *
     * @var string
     * @access private
     */
    protected $cachePrefix;

    /**
     * unitOfWork
     *
     * @var UnitOfWork
     * @access private
     */
    private $unitOfWork;

    /**
     * Constructor.
     *
     * @param RestClient      $restClient
     * @param Mapping         $mapping
     * @param Serializer|null $serializer
     */
    public function __construct(RestClient $restClient, Mapping $mapping, UnitOfWork $unitOfWork, Serializer $serializer = null)
    {
        $this->restClient = $restClient;
        $this->mapping = $mapping;
        $this->unitOfWork = $unitOfWork;
        if (!$serializer) {
            $serializer = new Serializer($this->mapping, $this->unitOfWork);
        }
        $this->serializer = $serializer;
        $this->serializer->setSdk($this);


        $this->modelHydrator = new ModelHydrator($this);
    }

    /**
     * setCacheItemPool
     *
     * @param CacheItemPoolInterface $cacheItemPool
     * @access public
     * @return SdkClient
     */
    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool, $cachePrefix = '')
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->cachePrefix = $cachePrefix;

        return $this;
    }

    /**
     * getCacheItemPool
     *
     * @return ?CacheItemPoolInterface
     */
    public function getCacheItemPool()
    {
        return $this->cacheItemPool;
    }

    /**
     * getCachePrefix
     *
     * @access public
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
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
        // get repository by key
        $metadata = $this->mapping->getClassMetadataByKey($modelName);
        if (!$metadata) {
            // get by classname
            $metadata = $this->mapping->getClassMetadata($modelName);
        }

        $modelName = $metadata->getModelName();

        if (!isset($this->repositoryList[$modelName])) {
            $repositoryName = $metadata->getRepositoryName() ?: '\Mapado\RestClientSdk\EntityRepository';
            $this->repositoryList[$modelName] = new $repositoryName($this, $this->restClient, $this->unitOfWork, $modelName);
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
     * @return \ProxyManager\Proxy\GhostObjectInterface
     */
    public function createProxy($id)
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);

        $modelName = $classMetadata->getModelName();

        $sdk = $this;

        if ($this->proxyManagerConfig) {
            $factory = new LazyLoadingGhostFactory($this->proxyManagerConfig);
        } else {
            $factory = new LazyLoadingGhostFactory();
        }

        $proxyModelName = preg_replace('/^\\\\*/', '', $modelName);

        $initializer = function (
            GhostObjectInterface $proxy,
            string $method,
            array $parameters,
            & $initializer,
            array $properties
        ) use (
            $sdk,
            $classMetadata,
            $id,
            $proxyModelName
        ) {
            $isAllowedMethod = $method === 'jsonSerialize'
                || $method === '__set';

            if (!$isAllowedMethod) {
                $initializer   = null; // disable initialization
                // load data and modify the object here
                if ($id) {
                    $repository = $sdk->getRepository($classMetadata->getModelName());
                    $model = $repository->find($id);

                    $attributeList = $classMetadata->getAttributeList();

                    foreach ($attributeList as $attribute) {
                        $getter = 'get' . ucfirst($attribute->getAttributeName());
                        $value = $model->$getter();
                        $properties['\0' . $proxyModelName . '\0' . $attribute->getAttributeName()] = $value;
                    }
                }

                return true; // confirm that initialization occurred correctly
            }
        };

        // initialize the proxy instance
        $instance = $factory->createProxy(
            $modelName,
            $initializer,
            [
                'skippedProperties' => [ $proxyModelName . '\0id' ]
            ]
        );

        // set the id of the object
        $idReflexion = new \ReflectionProperty(
            $modelName,
            $classMetadata->getIdentifierAttribute()->getAttributeName()
        );
        $idReflexion->setAccessible(true);
        $idReflexion->setValue($instance, $id);

        return $instance;
    }

    /**
     * Setter for fileCachePath
     *
     * @param string $fileCachePath
     * @return SdkClient
     */
    public function setFileCachePath($fileCachePath)
    {
        $this->proxyManagerConfig = new Configuration();
        $this->proxyManagerConfig->setProxiesTargetDir($fileCachePath);

        return $this;
    }
}
