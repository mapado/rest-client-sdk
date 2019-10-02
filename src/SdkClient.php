<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Model\ModelHydrator;
use Mapado\RestClientSdk\Model\Serializer;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * Cache item pool.
     *
     * @var ?CacheItemPoolInterface
     */
    protected $cacheItemPool;

    /**
     * Cache prefix.
     *
     * @var string
     */
    protected $cachePrefix;

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
     * @var ?Configuration
     */
    private $proxyManagerConfig;

    /**
     * unitOfWork
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(
        RestClient $restClient,
        Mapping $mapping,
        ?UnitOfWork $unitOfWork = null,
        ?Serializer $serializer = null
    ) {
        $this->restClient = $restClient;
        $this->mapping = $mapping;
        if (null === $unitOfWork) {
            $unitOfWork = new UnitOfWork($this->mapping);
        }
        $this->unitOfWork = $unitOfWork;
        if (null === $serializer) {
            $serializer = new Serializer($this->mapping, $this->unitOfWork);
        }
        $this->serializer = $serializer;
        $this->serializer->setSdk($this);

        $this->modelHydrator = new ModelHydrator($this);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function setCacheItemPool(
        CacheItemPoolInterface $cacheItemPool,
        string $cachePrefix = ''
    ): self {
        $this->cacheItemPool = $cacheItemPool;
        $this->cachePrefix = $cachePrefix;

        return $this;
    }

    public function getCacheItemPool(): ?CacheItemPoolInterface
    {
        return $this->cacheItemPool;
    }

    public function getCachePrefix(): string
    {
        return $this->cachePrefix;
    }

    public function getRepository(string $modelName): EntityRepository
    {
        // get repository by key
        $metadata = $this->mapping->getClassMetadataByKey($modelName);
        if (!$metadata) {
            // get by classname
            $metadata = $this->mapping->getClassMetadata($modelName);
        }

        $modelName = $metadata->getModelName();

        if (!isset($this->repositoryList[$modelName])) {
            $repositoryName = $metadata->getRepositoryName();

            $this->repositoryList[$modelName] = new $repositoryName(
                $this,
                $this->restClient,
                $this->unitOfWork,
                $modelName
            );
        }

        return $this->repositoryList[$modelName];
    }

    public function getRestClient(): RestClient
    {
        return $this->restClient;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    public function getModelHydrator(): ModelHydrator
    {
        return $this->modelHydrator;
    }

    public function createProxy(string $id): GhostObjectInterface
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);

        if (null === $classMetadata) {
            throw new \RuntimeException(
                "Unable to get classMetadata for key {$key}. This should not happen."
            );
        }

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
            &$initializer,
            array $properties
        ) use ($sdk, $classMetadata, $id, $proxyModelName) {
            $isAllowedMethod =
                'jsonSerialize' === $method ||
                '__set' === $method ||
                ('__isset' === $method && 'id' === $parameters['name']);

            if (!$isAllowedMethod) {
                $initializer = null; // disable initialization
                // load data and modify the object here
                if ($id) {
                    $repository = $sdk->getRepository(
                        $classMetadata->getModelName()
                    );
                    $model = $repository->find($id);

                    if (null !== $model) {
                        $attributeList = $classMetadata->getAttributeList();

                        foreach ($attributeList as $attribute) {
                            $value = $this->propertyAccessor->getValue(
                                $model,
                                $attribute->getAttributeName()
                            );
                            $properties[
                                "\0" .
                                    $proxyModelName .
                                    "\0" .
                                    $attribute->getAttributeName()
                            ] = $value;
                        }
                    }
                }

                return true; // confirm that initialization occurred correctly
            }
        };

        // initialize the proxy instance
        $instance = $factory->createProxy($modelName, $initializer, [
            'skippedProperties' => ["\0" . $proxyModelName . "\0id"],
        ]);

        // set the id of the object
        $idReflexion = new \ReflectionProperty(
            $modelName,
            $classMetadata->getIdentifierAttribute()->getAttributeName()
        );
        $idReflexion->setAccessible(true);
        $idReflexion->setValue($instance, $id);

        return $instance;
    }

    public function setFileCachePath(string $fileCachePath): self
    {
        $this->proxyManagerConfig = new Configuration();
        $this->proxyManagerConfig->setProxiesTargetDir($fileCachePath);

        return $this;
    }
}
