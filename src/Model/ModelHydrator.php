<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Collection\HydraCollection;
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
     * @access private
     */
    protected $sdk;

    /**
     * Constructor.
     *
     * @param SdkClient $sdk
     * @access public
     */
    public function __construct(SdkClient $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
      * convertId
      *
      * @param string $id
      * @param string $modelName
      * @access public
      * @return string
      */
    public function convertId($id, $modelName)
    {
        $id = (string) $id;

        // add slash if needed to have a valid hydra id
        if (strpos($id, '/') === false) {
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
     * @access public
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
     * convert API response to HydraCollection containing entities
     *
     * @param array $data
     * @param string $modelName
     * @access public
     * @return array
     */
    public function hydrateList($data, $modelName)
    {
        $collectionKey = $this->sdk->getMapping()
            ->getConfig()['collectionKey'];

        if (is_array($data) && ArrayHelper::arrayHas($data, $collectionKey)) {
            return $this->deserializeAll($data, $modelName);
        }

        return new HydraCollection();
    }

    /**
     * convert list of data as array to HydraCollection containing entities
     *
     * @param array  $data
     * @param string $modelName
     *
     * @access private
     * @return HydraCollection|HydraPaginatedCollection
     */
    private function deserializeAll($data, $modelName)
    {
        $collectionKey = $this->sdk->getMapping()
            ->getConfig()['collectionKey'];

        $itemList = array_map(
            function ($member) use ($modelName, $collectionKey) {
                return $this->deserialize($member, $modelName);
            },
            ArrayHelper::arrayGet($data, $collectionKey)
        );

        if (!empty($data['@type']) && $data['@type'] === 'hydra:PagedCollection') {
            return new HydraPaginatedCollection($itemList, $data);
        }

        return new HydraCollection($itemList);
    }

    /**
     * convert array to entity
     *
     * @param array  $data
     * @param string $modelName
     * @access private
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
}
