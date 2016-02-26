<?php

namespace Mapado\RestClientSdk\Client;

use Mapado\RestClientSdk\SdkClient;

/**
 * Class Client
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Client
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

   /**
     * convertId
     *
     * @param string $id
     * @param string $entityName
     * @access public
     * @return string
     */
    public function convertId($id, $entityName)
    {
        // add slash if needed to have a valid hydra id
        if (!strstr($id, '/')) {
            $mapping = $this->sdk->getMapping();
            $key = $mapping->getKeyFromModel($entityName);
            $id = $key . '/' . $id;

            if ($prefix = $mapping->getIdPrefix()) {
                $id = $prefix . '/' . $id;
            }
        }

        return $id;
    }

    /**
     * convert
     *
     * @param array $data
     * @param string $entityName
     * @access public
     * @return object
     */
    public function convert($data, $entityName)
    {
        $mapping = $this->sdk->getMapping();
        $key = $mapping->getKeyFromModel($entityName);
        $modelName = $mapping->getModelName($key);

        return $this->deserialize($data, $modelName);
    }

    /**
     * convertList
     *
     * @param array $data
     * @param string $entityName
     * @access public
     * @return array
     */
    public function convertList($data, $modelName)
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
}
