<?php
namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\SdkException;
use Symfony\Component\Cache\CacheItem;

class EntityRepository
{
    private $count = 0;

    /**
     * @object REST Client
     */
    protected $restClient;

    /**
     * @object The client for processing
     */
    protected $client;

    /**
     *
     */
    protected $class;

    /**
     * @var SDK object
     */
    protected $sdk;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $clientKey;

    /**
     * EntityRepository constructor
     *
     * @param object $sdkClient - the client to connect to the datasource with
     * @param object $restClient - client to process the http requests
     * @param string $entityName The entiy to work with
     */
    public function __construct($sdkClient, $restClient, $entityName)
    {
        $this->sdk = $sdkClient;
        $this->restClient = $restClient;
        $this->entityName = $entityName;
    }

    /**
     * find - finds one item of the entity based on the @REST\Id field in the entity
     *
     * @param string $id
     * @access public
     * @return object
     */
    public function find($id)
    {
        $hydrator = $this->sdk->getModelHydrator();
        $id = $hydrator->convertId($id, $this->entityName);

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
        $key = $mapping->getKeyFromModel($this->entityName);
        $prefix = $mapping->getIdPrefix();
        $path = (null == $prefix) ? $key : $prefix . '/' . $key;

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
            $this->saveToCache($entity->getId(), $entity);
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
        return $this->restClient->delete($model->getId());
    }

    /**
     * update
     *
     * @param object $model
     * @access public
     * @return void
     */
    public function update($model)
    {
        $data = $this->restClient->put(
            $model->getId(),
            $this->sdk->getSerializer()->serialize($model, $this->entityName)
        );

        $this->removeFromCache($model->getId());

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
    public function persist($model)
    {
        $mapping = $this->sdk->getMapping();
        $prefix = $mapping->getIdPrefix();
        $key = $mapping->getKeyFromModel($this->entityName);

        $path = (null == $prefix) ? $key : $prefix . '/' . $key;
        $data = $this->restClient->post($path, $this->sdk->getSerializer()->serialize($model, $this->entityName));

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
        $path = ((null == $prefix) ? $key : $prefix . '/' . $key);

        if (!empty($fieldName)) {
            $queryParams = [$fieldName => current($arguments)];
        } else {
            $queryParams = current($arguments);
        }
        $path .= '?' . http_build_query($this->convertQueryParameters($queryParams));

        $data =  $this->restClient->get($path);


        $hydrator = $this->sdk->getModelHydrator();

        if ($methodName == 'findOneBy') {
            // If more results are found but one is requested return the first hit.
            if (!empty($data['hydra:member'])) {
                $data = current($data['hydra:member']);
                return $hydrator->hydrate($data, $this->entityName);
            } else {
                return null;
            }
        }

        return $hydrator->hydrateList($data, $this->entityName);
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
        $sdk = $this->sdk;

        return array_map(
            function ($item) use ($sdk) {
                if (is_object($item) && method_exists($item, 'getId')) {
                    return $item->getId();
                }

                return $item;
            },
            $queryParameters
        );
    }

    /**
     * fetchFromCache
     *
     * @param array $data
     * @access private
     * @return object
     */
    private function fetchFromCache($key)
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
     * @param array $data
     * @access private
     * @return object
     */
    private function saveToCache($key, $value)
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
     * normalizeCacheKey
     *
     * @access private
     * @return string
     */
    private function normalizeCacheKey($key)
    {
        return preg_replace('~[\\/\{\}@:\(\)]~', '_', $key);
    }

    /**
     * removeFromCache
     *
     * @param string $key
     * @access private
     * @return boolean true if no cache or cache successfully cleared, false otherwise
     */
    private function removeFromCache($key)
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
}
