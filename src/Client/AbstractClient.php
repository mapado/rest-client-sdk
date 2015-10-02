<?php

namespace Mapado\RestClientSdk\Client;

use Mapado\RestClientSdk\SdkClient;

/**
 * Class AbstractClient
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
abstract class AbstractClient
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
     * find
     *
     * @param string $id
     * @access public
     * @return object
     */
    public function find($id)
    {
        $key = $this->sdk->getMapping()->getKeyFromClientName(get_called_class());
        $modelName = $this->sdk->getMapping()->getModelName($key);
        $data = $this->restClient->get($id);

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
    }

    /**
     * findAll
     *
     * @access public
     * @return array
     */
    public function findAll()
    {
        $key = $this->sdk->getMapping()->getKeyFromClientName(get_called_class());
        $data = $this->restClient->get('/v1/' . $key);

        if ($data && !empty($data['hydra:member'])) {
            $serializer = $this->sdk->getSerializer();

            $modelName = $this->sdk->getMapping()->getModelName($key);

            $list = [];
            if (!empty($data) && !empty($data['hydra:member'])) {
                foreach ($data['hydra:member'] as $instanceData) {
                    $list[] = $serializer->deserialize($instanceData, $modelName);
                }
            }

            return $list;
        }
        return [];
    }

    /**
     * remove
     *
     * @param object $model
     * @access public
     * @return void
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
        $key = $this->sdk->getMapping()->getKeyFromClientName(get_called_class());
        $modelName = $this->sdk->getMapping()->getModelName($key);

        $data = $this->restClient->put($model->getId(), $this->sdk->getSerializer()->serialize($model, $modelName));

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
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
        $prefix = $this->sdk->getMapping()->getIdPrefix();
        $key = $this->sdk->getMapping()->getKeyFromClientName(get_called_class());

        $path = $prefix . '/' . $key;
        $data = $this->restClient->post($path, $this->sdk->getSerializer()->serialize($model, $modelName));

        $modelName = $this->sdk->getMapping()->getModelName($key);

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
    }
}
