<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Collection\HydraCollection;
use Mapado\RestClientSdk\Collection\HydraPaginatedCollection;
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
        // add slash if needed to have a valid hydra id
        if (!strstr($id, '/')) {
            $mapping = $this->sdk->getMapping();
            $key = $mapping->getKeyFromModel($modelName);
            $id = $key . '/' . $id;

            if ($prefix = $mapping->getIdPrefix()) {
                $id = $prefix . '/' . $id;
            }
        }

        return $id;
    }

    /**
     * hydrate
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
     * hydrateList
     *
     * @param array $data
     * @param string $modelName
     * @access public
     * @return array
     */
    public function hydrateList($data, $modelName)
    {
        if (is_array($data) && !empty($data['hydra:member'])) {
            return $this->deserializeAll($data, $modelName);
        }

        return new HydraCollection();
    }

    /**
     * @param array  $data
     * @param string $modelName
     * @return HydraCollection|HydraPaginatedCollection
     */
    public function deserializeAll($data, $modelName)
    {
        $data['hydra:member'] = array_map(
            function ($member) use ($modelName) {
                return $this->deserialize($member, $modelName);
            },
            $data['hydra:member']
        );

        $hydratedList = new HydraCollection($data);

        if (!empty($data['@type'])) {
            if ($data['@type'] === 'hydra:PagedCollection') {
                $hydratedList = new HydraPaginatedCollection($data);
            }
        }

        return $hydratedList;
    }

    /**
     * deserialize
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
