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
     * @var RestClient
     * @access protected
     */
    protected $restClient;

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
        $this->restClient = $this->sdk->getRestClient();
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
     * @access private
     * @return string
     */
    public function convertId($id, $entityName = null)
    {
        // add slash if needed to have a valid hydra id
        if (!strstr($id, '/')) {
            $mapping = $this->sdk->getMapping($entityName);
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
     * @access protected
     * @return object
     */
    public function convert($data, $entityName = null)
    {
        $mapping = $this->sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);
        $modelName = $mapping->getModelName($key);

        return $this->deserialize($data, $modelName);
    }

    /**
     * convertList
     *
     * @access protected
     * @return array
     */
    public function convertList($data, $entityName = null)
    {
        if ($data && is_array($data) && !empty($data['hydra:member'])) {
//            $mapping = $this->sdk->getMapping();
//            $key = $mapping->getKeyFromClientName(get_called_class());
            $mapping = $this->sdk->getMapping($entityName);
            $key = $mapping->getKeyFromModel($entityName);
            $modelName = $this->sdk->getMapping()->getModelName($key);

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
