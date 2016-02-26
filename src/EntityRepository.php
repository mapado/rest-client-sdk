<?php
namespace Mapado\RestClientSdk;

class EntityRepository
{
    /**
     * @object REST Client
     */
    protected $restClient;

    /**
     * @object The client for processing
     */
    protected $client;

    /**
     * @object The Repository to be used
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
     *
     * @param type $client - the client to process the data with
     * @param object $sdkClient - the client to connect to the datasource with
     * @param object $restClient - cleitn to process the http requests
     * @param type $class The entiy to work with
     */
    public function __construct($client, $sdkClient, $restClient, $class)
    {
        $this->client      = $client;
        $this->sdk         = $sdkClient;
        $this->restClient  = $restClient;
        $this->class      = $class;
        $this->entityName  = getclass($this->class);
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
        $id = $this->client->convertId($id, $this->entityName);
        $data = $this->restClient->get($id);
        return $this->client->convert($data, $this->entityName);
    }

    /**
     * findAll
     *
     * @access public
     * @return array
     */
    public function findAll()
    {
        $entityName = getclass($this->class);
        $mapping = $this->sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);
        $prefix = $mapping->getIdPrefix();
        $path = (null == $prefix) ? $key : $prefix . '/' . $key;
        $data = $this->restClient->get($path);
        return $this->client->convertList($data, $this->entityName);
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
        $entityName = getclass($this->class);
        $mapping = $this->sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);
        $modelName = $this->sdk->getMapping()->getModelName($key);

        $data = $this->restClient->put($model->getId(), $this->sdk->getSerializer()->serialize($model, $modelName));

        return $this->client->convert($data, $this->entityName);
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
        $entityName = getclass($this->class);
        $mapping = $this->sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);
        $modelName = $this->sdk->getMapping()->getModelName($key);

        $path = (null == $prefix) ? $key : $prefix . '/' . $key;
        $data = $this->restClient->post($path, $this->sdk->getSerializer()->serialize($model, $modelName));

        return $this->client->convert($data, $this->entityName);
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
                $method = 'findBy';
                break;

            case (0 === strpos($method, 'findOneBy')):
                $fieldName = strtolower(substr($method, 9));
                $method = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        if (empty($arguments)) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        $entityName = getclass($this->class);
        $mapping = $this->sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);
        $prefix = $mapping->getIdPrefix();
        $path = ((null == $prefix) ? $key : $prefix . '/' . $key) . '?';

        if ($fieldName != '') {
            $path .= $fieldName .'='. array_shift($arguments);
        } else {
            foreach (array_shift($arguments) as $key => $value) {
                $path .= strtolower($key) . '=' . $value .'&';
            }
            $path = rtrim($path, "&");
        }

        $data =  $this->restClient->get($path);
        if ($method == 'findOneBy') {
            // If more results are found but one is requested return the first hit.
            if (count($data['hydra:member']) > 1) {
                $data = array_shift($data['hydra:member']);
            }
            return $this->client->convert($data, $this->entityName);
        }
        return $this->client->convertList($data, $this->entityName);
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->getEntityName();
    }

    /**
     * @return Mapping\ClassMetadata
     */
    protected function getClassMetadata()
    {
        return $this->class;
    }
}
