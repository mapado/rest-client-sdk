<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Helper\ArrayHelper;

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
     * @var \Mapado\RestClientSdk\Mapping\ClassMetadata
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
        $entityName
    ) {
        $this->sdk = $sdkClient;
        $this->restClient = $restClient;
        $this->unitOfWork = $unitOfWork;
        $this->entityName = $entityName;
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param mixed  $arguments
     *
     * @return array|object the found entity/entities
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case 0 === strpos($method, 'findBy'):
                $fieldName = strtolower(substr($method, 6));
                $methodName = 'findBy';
                break;

            case 0 === strpos($method, 'findOneBy'):
                $fieldName = strtolower(substr($method, 9));
                $methodName = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    'Undefined method \'' .
                    $method .
                    '\'. The method name must start with
                    either findBy or findOneBy!'
                );
        }

        if (empty($arguments)) {
            throw new SdkException(
                'You need to pass a parameter to ' . $method
            );
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
            $entityList = ArrayHelper::arrayGet($data, $collectionKey);
            if (!empty($entityList)) {
                $data = current($entityList);
                $hydratedData = $hydrator->hydrate($data, $this->entityName);

                $identifier = $hydratedData->{$this->getClassMetadata()->getIdGetter()}();
                $this->unitOfWork->registerClean($identifier, $hydratedData);
                $this->saveToCache($identifier, $hydratedData);
            } else {
                $hydratedData = null;
            }
        } else {
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
     * @param string $id          id of the element to fetch
     * @param array  $queryParams query parameters to add to the query
     *
     * @return object
     */
    public function find($id, $queryParams = [])
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
        $entity = $hydrator->hydrate($data, $this->entityName);

        // cache entity
        $this->saveToCache($id, $entity);
        $this->unitOfWork->registerClean($id, $entity); // another register clean will be made in the Serializer if the id different from the called uri

        return $entity;
    }

    /**
     * findAll
     *
     * @return array|object
     */
    public function findAll()
    {
        $mapping = $this->sdk->getMapping();
        $key = $this->getClassMetadata()->getKey();
        $prefix = $mapping->getIdPrefix();
        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        $entityListFromCache = $this->fetchFromCache($path);

        // if entityList is found in cache, return it
        if (false !== $entityListFromCache) {
            return $entityListFromCache;
        }

        $data = $this->restClient->get($path);

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
     * remove
     *
     * @param object $model
     *
     * @TODO STILL NEEDS TO BE CONVERTED TO ENTITY MODEL
     */
    public function remove($model)
    {
        $identifier = $model->{$this->getClassMetadata()->getIdGetter()}();
        $this->removeFromCache($identifier);
        $this->unitOfWork->clear($identifier);

        $this->restClient->delete($identifier);
    }

    /**
     * update
     *
     * @param object $model
     *
     * @return object
     */
    public function update(
        $model,
        $serializationContext = [],
        $queryParams = []
    ) {
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

        $data = $this->restClient->put(
            $this->addQueryParameter($identifier, $queryParams),
            $newSerializedModel
        );

        $this->removeFromCache($identifier);
        $this->unitOfWork->registerClean($identifier, $data);
        $hydrator = $this->sdk->getModelHydrator();

        return $hydrator->hydrate($data, $this->entityName);
    }

    /**
     * persist
     *
     * @param object $model
     *
     * @return object
     */
    public function persist(
        $model,
        $serializationContext = [],
        $queryParams = []
    ) {
        $mapping = $this->sdk->getMapping();
        $prefix = $mapping->getIdPrefix();
        $key = $mapping->getKeyFromModel($this->entityName);

        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        $oldSerializedModel = $this->getClassMetadata()->getDefaultSerializedModel();
        $newSerializedModel = $this->sdk->getSerializer()->serialize(
            $model,
            $this->entityName,
            $serializationContext
        );

        $diff = $this->unitOfWork->getDirtyData(
            $newSerializedModel,
            $oldSerializedModel,
            $this->getClassMetadata()
        );

        $data = $this->restClient->post(
            $this->addQueryParameter($path, $queryParams),
            $diff
        );

        $hydrator = $this->sdk->getModelHydrator();

        return $hydrator->hydrate($data, $this->entityName);
    }

    /**
     * fetchFromCache
     *
     * @param string $key
     *
     * @return object|false
     */
    protected function fetchFromCache($key)
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

    /**
     * saveToCache
     *
     * @return object
     */
    protected function saveToCache($key, $value)
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
     * removeFromCache
     *
     * @param string $key
     *
     * @return bool true if no cache or cache successfully cleared, false otherwise
     */
    protected function removeFromCache($key)
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

    /**
     * addQueryParameter
     *
     * @param string $path path to call
     * @param array $params query parameters to add
     *
     * @return string
     */
    protected function addQueryParameter($path, $params = [])
    {
        if (empty($params)) {
            return $path;
        }

        return $path . '?' . http_build_query($params);
    }

    /**
     * convertQueryParameters
     *
     * @param array $queryParameters
     *
     * @return array
     */
    private function convertQueryParameters($queryParameters)
    {
        $mapping = $this->sdk->getMapping();

        return array_map(function ($item) use ($mapping) {
            if (is_object($item)) {
                $classname = get_class($item);

                if ($mapping->hasClassMetadata($classname)) {
                    $idGetter = $mapping->getClassMetadata(
                        $classname
                    )->getIdGetter();

                    return $item->{$idGetter}();
                }
            }

            return $item;
        }, $queryParameters);
    }

    /**
     * normalizeCacheKey
     *
     * @return string
     */
    private function normalizeCacheKey($key)
    {
        return preg_replace('~[\\/\{\}@:\(\)]~', '_', $key);
    }

    private function getClassMetadata()
    {
        if (!isset($this->classMetadata)) {
            $this->classMetadataCache = $this->sdk->getMapping()->getClassMetadata(
                $this->entityName
            );
        }

        return $this->classMetadataCache;
    }
}
