<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Collection\Collection;
use Mapado\RestClientSdk\Exception\HydratorException;
use Mapado\RestClientSdk\Exception\RestException;
use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Exception\UnexpectedTypeException;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Psr\Http\Message\ResponseInterface;

class EntityRepository
{
    /**
     * REST Client.
     *
     * @var RestClient
     */
    protected $restClient;

    /**
     * SDK Client.
     *
     * @var SdkClient
     */
    protected $sdk;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * classMetadataCache
     *
     * @var ClassMetadata
     */
    private $classMetadataCache;

    /**
     * unitOfWork
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * EntityRepository constructor
     *
     * @param SdkClient  $sdkClient  The client to connect to the datasource with
     * @param RestClient $restClient The client to process the http requests
     * @param string     $entityName The entity to work with
     */
    public function __construct(
        SdkClient $sdkClient,
        RestClient $restClient,
        UnitOfWork $unitOfWork,
        string $entityName
    ) {
        $this->sdk = $sdkClient;
        $this->restClient = $restClient;
        $this->unitOfWork = $unitOfWork;
        $this->entityName = $entityName;
    }

    /**
     * Adds support for magic finders.
     *
     * @return array|object|null the found entity/entities
     */
    public function __call(string $method, array $arguments)
    {
        switch (true) {
            case 0 === mb_strpos($method, 'findBy'):
                $fieldName = mb_strtolower(mb_substr($method, 6));
                $methodName = 'findBy';
                break;

            case 0 === mb_strpos($method, 'findOneBy'):
                $fieldName = mb_strtolower(mb_substr($method, 9));
                $methodName = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException('Undefined method \'' . $method . '\'. The method name must start with
                    either findBy or findOneBy!');
        }

        if (empty($arguments)) {
            throw new SdkException('You need to pass a parameter to ' . $method);
        }

        $mapping = $this->sdk->getMapping();
        $key = $mapping->getKeyFromModel($this->entityName);
        $prefix = $mapping->getIdPrefix();
        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        if (!empty($fieldName)) {
            $queryParams = [$fieldName => current($arguments)];
        } else {
            $queryParams = current($arguments);
        }
        $path .=
            '?' . http_build_query($this->convertQueryParameters($queryParams));

        // if entityList is found in cache, return it
        $entityListFromCache = $this->fetchFromCache($path);
        if (false !== $entityListFromCache) {
            return $entityListFromCache;
        }

        $data = $this->restClient->get($path);
        $hydrator = $this->sdk->getModelHydrator();

        if ('findOneBy' == $methodName) {
            // If more results are found but one is requested return the first hit.
            $collectionKey = $mapping->getConfig()['collectionKey'];

            $data = $this->assertArray($data, $methodName);
            $entityList = ArrayHelper::arrayGet($data, $collectionKey);
            if (!empty($entityList)) {
                $data = current($entityList);
                $hydratedData = $hydrator->hydrate($data, $this->entityName);

                $identifier = $hydratedData->{$this->getClassMetadata()->getIdGetter()}();
                if (null !== $hydratedData) {
                    $this->unitOfWork->registerClean(
                        $identifier,
                        $hydratedData
                    );
                }
                $this->saveToCache($identifier, $hydratedData);
            } else {
                $hydratedData = null;
            }
        } else {
            $data = $this->assertNotObject($data, $methodName);
            $hydratedData = $hydrator->hydrateList($data, $this->entityName);

            // then cache each entity from list
            foreach ($hydratedData as $entity) {
                $identifier = $entity->{$this->getClassMetadata()->getIdGetter()}();
                $this->saveToCache($identifier, $entity);
                $this->unitOfWork->registerClean($identifier, $entity);
            }
        }

        $this->saveToCache($path, $hydratedData);

        return $hydratedData;
    }

    /**
     * find - finds one item of the entity based on the @REST\Id field in the entity
     *
     * @param string|int|mixed $id          id of the element to fetch
     * @param array  $queryParams query parameters to add to the query
     */
    public function find($id, array $queryParams = []): ?object
    {
        $hydrator = $this->sdk->getModelHydrator();
        $id = $hydrator->convertId($id, $this->entityName);

        $id = $this->addQueryParameter($id, $queryParams);

        // if entity is found in cache, return it
        $entityFromCache = $this->fetchFromCache($id);
        if (false != $entityFromCache) {
            return $entityFromCache;
        }

        $data = $this->restClient->get($id);
        $data = $this->assertNotObject($data, __METHOD__);

        $entity = $hydrator->hydrate($data, $this->entityName);

        // cache entity
        $this->saveToCache($id, $entity);
        if (null !== $entity) {
            $this->unitOfWork->registerClean($id, $entity); // another register clean will be made in the Serializer if the id different from the called uri
        }

        return $entity;
    }

    public function findAll(): Collection
    {
        $mapping = $this->sdk->getMapping();
        $key = $this->getClassMetadata()->getKey();
        $prefix = $mapping->getIdPrefix();
        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        $entityListFromCache = $this->fetchFromCache($path);

        // if entityList is found in cache, return it
        if (false !== $entityListFromCache) {
            if (!$entityListFromCache instanceof Collection) {
                throw new \RuntimeException('Entity list in cache should be an instance of ' . Collection::class . '. This should not happen.');
            }

            return $entityListFromCache;
        }

        $data = $this->restClient->get($path);
        $data = $this->assertNotObject($data, __METHOD__);

        $hydrator = $this->sdk->getModelHydrator();
        $entityList = $hydrator->hydrateList($data, $this->entityName);

        // cache entity list
        $this->saveToCache($path, $entityList);

        // then cache each entity from list
        foreach ($entityList as $entity) {
            $identifier = $entity->{$this->getClassMetadata()->getIdGetter()}();
            $this->unitOfWork->registerClean($identifier, $entity);
            $this->saveToCache($identifier, $entity);
        }

        return $entityList;
    }

    /**
     * remove entity
     *
     * @TODO STILL NEEDS TO BE CONVERTED TO ENTITY MODEL
     */
    public function remove(object $model): void
    {
        $identifier = $model->{$this->getClassMetadata()->getIdGetter()}();
        $this->removeFromCache($identifier);
        $this->unitOfWork->clear($identifier);

        $this->restClient->delete($identifier);
    }

    public function update(
        object $model,
        array $serializationContext = [],
        array $queryParams = []
    ): object {
        $identifier = $model->{$this->getClassMetadata()->getIdGetter()}();
        $serializer = $this->sdk->getSerializer();
        $newSerializedModel = $serializer->serialize(
            $model,
            $this->entityName,
            $serializationContext
        );

        $oldModel = $this->unitOfWork->getDirtyEntity($identifier);
        if ($oldModel) {
            $oldSerializedModel = $serializer->serialize(
                $oldModel,
                $this->entityName,
                $serializationContext
            );
            $newSerializedModel = $this->unitOfWork->getDirtyData(
                $newSerializedModel,
                $oldSerializedModel,
                $this->getClassMetadata()
            );
        }

        $path = $this->addQueryParameter($identifier, $queryParams);

        $data = $this->restClient->put($path, $newSerializedModel);
        $data = $this->assertArray($data, __METHOD__);

        $this->removeFromCache($identifier);
        // $this->unitOfWork->registerClean($identifier, $data);
        $hydrator = $this->sdk->getModelHydrator();
        $out = $hydrator->hydrate($data, $this->entityName);

        if (null === $out) {
            throw new HydratorException("Unable to convert data from PUT request ({$path}) to an instance of {$this->entityName}. Maybe you have a custom hydrator returning null?");
        }

        return $out;
    }

    public function persist(
        object $model,
        array $serializationContext = [],
        array $queryParams = []
    ): object {
        $mapping = $this->sdk->getMapping();
        $prefix = $mapping->getIdPrefix();
        $key = $mapping->getKeyFromModel($this->entityName);

        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        $oldSerializedModel = $this->getClassMetadata()->getDefaultSerializedModel();
        $newSerializedModel = $this->sdk
            ->getSerializer()
            ->serialize($model, $this->entityName, $serializationContext);

        $diff = $this->unitOfWork->getDirtyData(
            $newSerializedModel,
            $oldSerializedModel,
            $this->getClassMetadata()
        );

        $data = $this->restClient->post(
            $this->addQueryParameter($path, $queryParams),
            $diff
        );
        $data = $this->assertNotObject($data, __METHOD__);

        if (null === $data) {
            throw new RestException("No data found after sending a `POST` request to {$path}. Did the server returned a 4xx or 5xx status code?", $path);
        }

        $hydrator = $this->sdk->getModelHydrator();

        $out = $hydrator->hydrate($data, $this->entityName);

        if (null === $out) {
            throw new HydratorException("Unable to convert data from POST request ({$path}) to an instance of {$this->entityName}. Maybe you have a custom hydrator returning null?");
        }

        return $out;
    }

    /**
     * @return object|false
     */
    protected function fetchFromCache(string $key)
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if ($cacheItemPool) {
            $cacheKey = $this->sdk->getCachePrefix() . $key;
            if ($cacheItemPool->hasItem($cacheKey)) {
                $cacheItem = $cacheItemPool->getItem($cacheKey);
                $cacheData = $cacheItem->get();

                return $cacheData;
            }
        }

        return false;
    }

    protected function saveToCache(string $key, ?object $value): void
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if ($cacheItemPool) {
            $cacheKey = $this->sdk->getCachePrefix() . $key;

            if (!$cacheItemPool->hasItem($cacheKey)) {
                $cacheItem = $cacheItemPool->getItem($cacheKey);
                $cacheItem->set($value);
                $cacheItemPool->save($cacheItem);
            }
        }
    }

    /**
     * remove from cache
     *
     * @return bool true if no cache or cache successfully cleared, false otherwise
     */
    protected function removeFromCache(string $key): bool
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if ($cacheItemPool) {
            $cacheKey = $this->sdk->getCachePrefix() . $key;

            if ($cacheItemPool->hasItem($cacheKey)) {
                return $cacheItemPool->deleteItem($cacheKey);
            }
        }

        return true;
    }

    protected function addQueryParameter(
        string $path,
        array $params = []
    ): string {
        if (empty($params)) {
            return $path;
        }

        return $path . '?' . http_build_query($params);
    }

    private function convertQueryParameters(array $queryParameters): array
    {
        $mapping = $this->sdk->getMapping();

        return array_map(function ($item) use ($mapping) {
            if (is_object($item)) {
                $classname = get_class($item);

                if ($mapping->hasClassMetadata($classname)) {
                    $idGetter = $mapping
                        ->getClassMetadata($classname)
                        ->getIdGetter();

                    return $item->{$idGetter}();
                }
            }

            return $item;
        }, $queryParameters);
    }

    private function normalizeCacheKey(string $key): string
    {
        $out = preg_replace('~[\\/\{\}@:\(\)]~', '_', $key);

        if (null === $out) {
            throw new \RuntimeException('Unable to normalize cache key. This should not happen.');
        }

        return $out;
    }

    private function getClassMetadata(): ClassMetadata
    {
        if (!isset($this->classMetadata)) {
            $this->classMetadataCache = $this->sdk
                ->getMapping()
                ->getClassMetadata($this->entityName);
        }

        return $this->classMetadataCache;
    }

    /**
     * @param array|ResponseInterface|null $data
     */
    private function assertArray($data, string $methodName): array
    {
        if (is_array($data)) {
            return $data;
        }

        $type = null === $data ? 'null' : get_class($data);

        throw new UnexpectedTypeException("Return of method {$methodName} should be an array. {$type} given.");
    }

    /**
     * @param array|ResponseInterface|null $data
     *
     * @return array|null
     */
    private function assertNotObject($data, string $methodName)
    {
        if (!is_object($data)) {
            return $data;
        }

        $type = get_class($data);

        throw new UnexpectedTypeException("Return of method {$methodName} should be an array. {$type} given.");
    }
}
