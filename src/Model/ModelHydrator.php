<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\SdkClient;

/**
 * Class ModelHydrator
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
     * __construct
     *
     * @param RestClient
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
        if ($data && is_array($data) && !empty($data['hydra:member'])) {
            $list = [];
            if (!empty($data) && !empty($data['hydra:member'])) {
                foreach ($data['hydra:member'] as $instanceData) {
                    $list[] = $this->deserialize($instanceData, $modelName);
                }
            }

            return $list;
        }

        return [];
    }

    /**
     * deserialize
     *
     * @param array $data
     * @param string $modelName
     * @access private
     * @return object
     */
    private function deserialize($data, $modelName)
    {
        if (!$data) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
    }
}
