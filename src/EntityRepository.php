<?php

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Symfony\Component\Cache\CacheItem;

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
     * @access private
     */
    private $classMetadataCache;

    /**
     * EntityRepository constructor
     *
     * @param SdkClient  $sdkClient  The client to connect to the datasource with
     * @param RestClient $restClient The client to process the http requests
     * @param string     $entityName The entity to work with
     */
    public function __construct(SdkClient $sdkClient, RestClient $restClient, $entityName)
    {
        $this->sdk        = $sdkClient;
        $this->restClient = $restClient;
        $this->entityName = $entityName;
    }

    /**
     * find - finds one item of the entity based on the @REST\Id field in the entity
     *
     * @param string $id          id of the element to fetch
     * @param array  $queryParams query parameters to add to the query
     * @access public
     * @return object
     */
    public function find($id, $queryParams = [])
    {
        $hydrator = $this->sdk->getModelHydrator();
        $id = $hydrator->convertId($id, $this->entityName);

        $id = $this->addQueryParameter($id, $queryParams);

        // if entity is found in cache, return it
        $entityFromCache = $this->fetchFromCache($id);
        if ($entityFromCache != false) {
            return $entityFromCache;
        }

        $data = $this->restClient->get($id);
        $entity = $hydrator->hydrate($data, $this->entityName);

        // cache entity
        $this->saveToCache($id, $entity);

        return $entity;
    }

    /**
     * findAll
     *
     * @access public
     * @return array
     */
    public function findAll()
    {
        $mapping = $this->sdk->getMapping();
        $classMetadata = $this->getClassMetadata();
        $key = $classMetadata->getKey();
        $prefix = $mapping->getIdPrefix();
        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;

        $entityListFromCache = $this->fetchFromCache($path);

        // if entityList is found in cache, return it
        if ($entityListFromCache !== false) {
            return $entityListFromCache;
        }

        $data = $this->restClient->get($path);

        $hydrator = $this->sdk->getModelHydrator();
        $entityList = $hydrator->hydrateList($data, $this->entityName);

        // cache entity list
        $this->saveToCache($path, $entityList);

        // then cache each entity from list
        foreach ($entityList as $entity) {
            $this->saveToCache($this->getIdentifier($entity), $entity);
        }

        return $entityList;
    }

    /**
     * remove
     *
     * @param object $model
     * @access public
     * @return void
     *
     * @TODO STILL NEEDS TO BE CONVERTED TO ENTITY MODEL
     */
    public function remove($model)
    {
        $identifier = $this->getIdentifier($model);
        $this->removeFromCache($identifier);

        return $this->restClient->delete($identifier);
    }

    /**
     * update
     *
     * @param object $model
     * @access public
     * @return void
     */
    public function update($model, $serializationContext = [])
    {
        $data = $this->restClient->put(
            $this->getIdentifier($model),
            $this->sdk->getSerializer()->serialize($model, $this->entityName, $serializationContext)
        );

        $this->removeFromCache($this->getIdentifier($model));

        $hydrator = $this->sdk->getModelHydrator();
        return $hydrator->hydrate($data, $this->entityName);
    }

    /**
     * persist
     *
     * @param object $model
     * @access public
     * @return void
     */
    public function persist($model, $serializationContext = [])
    {
        $mapping = $this->sdk->getMapping();
        $prefix = $mapping->getIdPrefix();
        $key = $mapping->getKeyFromModel($this->entityName);

        $path = empty($prefix) ? '/' . $key : $prefix . '/' . $key;
        $data = $this->restClient->post($path, $this->sdk->getSerializer()->serialize($model, $this->entityName, $serializationContext));

        $hydrator = $this->sdk->getModelHydrator();
        return $hydrator->hydrate($data, $this->entityName);
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param mixed  $arguments
     *
     * @return array|object The found entity/entities.
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $fieldName = strtolower(substr($method, 6));
                $methodName = 'findBy';
                break;

            case (0 === strpos($method, 'findOneBy')):
                $fieldName = strtolower(substr($method, 9));
                $methodName = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    'Undefined method \'' . $method . '\'. The method name must start with
                    either findBy or findOneBy!'
                );
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
        $path .= '?' . http_build_query($this->convertQueryParameters($queryParams));

        // if entityList is found in cache, return it
        $entityListFromCache = $this->fetchFromCache($path);
        if ($entityListFromCache !== false) {
            return $entityListFromCache;
        }

        $data = $this->restClient->get($path);

        $hydrator = $this->sdk->getModelHydrator();

        if ($methodName == 'findOneBy') {
            // If more results are found but one is requested return the first hit.
            $collectionKey = $mapping->getConfig()['collectionKey'];
            if (ArrayHelper::arrayHas($data, $collectionKey)) {
                $data = current(ArrayHelper::arrayGet($data, $collectionKey));
                $hydratedData = $hydrator->hydrate($data, $this->entityName);

                $this->saveToCache($this->getIdentifier($hydratedData), $hydratedData);
            } else {
                $hydratedData = null;
            }
        } else {
            $hydratedData = $hydrator->hydrateList($data, $this->entityName);

            // then cache each entity from list
            foreach ($hydratedData as $entity) {
                $this->saveToCache($this->getIdentifier($entity), $entity);
            }
        }

        $this->saveToCache($path, $hydratedData);

        return $hydratedData;
    }

    /**
     * fetchFromCache
     *
     * @access protected
     * @param string $key
     * @return object|false
     */
    protected function fetchFromCache($key)
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if (isset($cacheItemPool)) {
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
     * @access protected
     * @return object
     */
    protected function saveToCache($key, $value)
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if (isset($cacheItemPool)) {
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
     * @access private
     * @return boolean true if no cache or cache successfully cleared, false otherwise
     */
    protected function removeFromCache($key)
    {
        $key = $this->normalizeCacheKey($key);
        $cacheItemPool = $this->sdk->getCacheItemPool();
        if (isset($cacheItemPool)) {
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
     * @access private
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
     * @access private
     * @return array
     */
    private function convertQueryParameters($queryParameters)
    {
        $mapping = $this->sdk->getMapping();

        return array_map(
            function ($item) use ($mapping) {
                if (is_object($item)) {
                    $classname = get_class($item);

                    if ($mapping->hasClassMetadata($classname)) {
                        $idAttr = $mapping->getClassMetadata($classname)
                            ->getIdentifierAttribute();

                        if ($idAttr) {
                            $idGetter = 'get' . ucfirst($idAttr->getAttributeName());

                            return $item->{$idGetter}();
                        }
                    }

                    if (method_exists($item, 'getId')) {
                        return $item->getId();
                    }
                }

                return $item;
            },
            $queryParameters
        );
    }

    /**
     * normalizeCacheKey
     *
     * @access private
     * @return string
     */
    private function normalizeCacheKey($key)
    {
        return preg_replace('~[\\/\{\}@:\(\)]~', '_', $key);
    }

    private function getClassMetadata()
    {
        if (!isset($this->classMetadata)) {
            $this->classMetadataCache = $this->sdk
                ->getMapping()
                ->getClassMetadata($this->entityName);
        }

        return $this->classMetadataCache;
    }

    private function getIdentifier($entity)
    {
        $mapping = $this->sdk->getMapping();
        $classMetadata = $this->getClassMetadata();

        if ($classMetadata->getIdentifierAttribute()) {
            $idAttr = $classMetadata->getIdentifierAttribute()
                ->getAttributeName();
            $idGetter = 'get' . ucfirst($idAttr);
        } else {
            $idGetter = 'getId';
        }

        return $entity->{$idGetter}();
    }
}
