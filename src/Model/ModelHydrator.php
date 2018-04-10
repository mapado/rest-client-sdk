<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Collection\Collection;
use Mapado\RestClientSdk\Collection\HalCollection;
use Mapado\RestClientSdk\Collection\HydraPaginatedCollection;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\SdkClient;

/**
 * Class ModelHydrator
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ModelHydrator
{
    /**
     * sdk
     *
     * @var SdkClient
     */
    protected $sdk;

    /**
     * Constructor.
     *
     * @param SdkClient $sdk
     */
    public function __construct(SdkClient $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
     * convertId
     *
     * @param mixed $id
     * @param string $modelName
     *
     * @return string
     */
    public function convertId($id, $modelName)
    {
        $id = (string) $id;

        // add slash if needed to have a valid hydra id
        if (false === strpos($id, '/')) {
            $mapping = $this->sdk->getMapping();
            $key = $mapping->getKeyFromModel($modelName);
            $id = '/' . $key . '/' . $id;

            if ($prefix = $mapping->getIdPrefix()) {
                $id = $prefix . $id;
            }
        }

        return $id;
    }

    /**
     * convert data as array to entity
     *
     * @param array $data
     * @param string $modelName
     *
     * @return object
     */
    public function hydrate($data, $modelName)
    {
        $mapping = $this->sdk->getMapping();
        $key = $mapping->getKeyFromModel($modelName);
        $modelName = $mapping->getModelName($key);

        return $this->deserialize($data, $modelName);
    }

    /**
     * convert API response to Collection containing entities
     *
     * @param array $data
     * @param string $modelName
     *
     * @return Collection
     */
    public function hydrateList($data, $modelName)
    {
        $collectionKey = $this->sdk->getMapping()
            ->getConfig()['collectionKey'];

        if (is_array($data) && ArrayHelper::arrayHas($data, $collectionKey)) {
            return $this->deserializeAll($data, $modelName);
        }

        return new Collection();
    }

    /**
     * convert list of data as array to Collection containing entities
     *
     * @param array  $data
     * @param string $modelName
     *
     * @return Collection
     */
    private function deserializeAll($data, $modelName)
    {
        $collectionKey = $this->sdk->getMapping()
            ->getConfig()['collectionKey'];

        $itemList = array_map(
            function ($member) use ($modelName) {
                return $this->deserialize($member, $modelName);
            },
            ArrayHelper::arrayGet($data, $collectionKey)
        );

        $extraProperties = array_filter(
            $data,
            function ($key) use ($collectionKey) {
                return $key !== $collectionKey;
            },
            ARRAY_FILTER_USE_KEY
        );

        $collectionClassName = $this->guessCollectionClassname($data);

        return new $collectionClassName($itemList, $extraProperties);
    }

    /**
     * convert array to entity
     *
     * @param array  $data
     * @param string $modelName
     *
     * @return object|null
     */
    private function deserialize($data, $modelName)
    {
        if (empty($data)) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
    }

    /**
     * guess collection classname according to response data
     *
     * @param array $data
     *
     * @return string
     */
    private function guessCollectionClassname($data)
    {
        switch (true) {
            case !empty($data['@type']) && 'hydra:PagedCollection' === $data['@type']:
                return HydraPaginatedCollection::class;

            case array_key_exists('_embedded', $data):
                return HalCollection::class;

            default:
                return Collection::class;
        }
    }
}
