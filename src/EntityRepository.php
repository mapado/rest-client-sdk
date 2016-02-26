<?php
namespace Mapado\RestClientSdk;

class EntityRepository
{
    /**
     * @object REST Client
     */
    protected $_restClient;
    
    /**
     * @object The client for processing 
     */
    protected $_client;

    /**
     * @object The Repository to be used
     */
    protected $_class;

    /**
     * @var SDK object 
     */
    protected $_sdk;
    
    /**
     * @var string 
     */
    protected $_entityName;
    
    /**
     * @var string 
     */
    protected $_clientKey;
    
    /**
     * 
     * @param type $client - the client to process the data with
     * @param object $sdkClient - the client to connect to the datasource with
     * @param object $restClient - cleitn to process the http requests
     * @param type $class The entiy to work with
     */
    public function __construct($client, $sdkClient, $restClient, $class)
    {
        $this->_client      = $client;
        $this->_sdk         = $sdkClient;
        $this->_restClient  = $restClient;
        $this->_class      = $class;
        $this->_entityName  = get_class($this->_class);
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
        $id = $this->_client->convertId($id, $this->_entityName);
        $data = $this->_restClient->get($id);
        return $this->_client->convert($data, $this->_entityName);
    }

    /**
     * findAll
     *
     * @access public
     * @return array
     */
    public function findAll()
    {
        $entityName = get_class($this->_class);
        $mapping = $this->_sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);        
        $prefix = $mapping->getIdPrefix();
        $path = (null == $prefix) ? $key : $prefix . '/' . $key;
        $data = $this->_restClient->get($path);
        return $this->_client->convertList($data, $this->_entityName);
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
        return $this->_restClient->delete($model->getId());
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
        $entityName = get_class($this->_class);
        $mapping = $this->_sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName); 
        $modelName = $this->_sdk->getMapping()->getModelName($key);

        $data = $this->_restClient->put($model->getId(), $this->_sdk->getSerializer()->serialize($model, $modelName));

        return $this->_client->convert($data, $this->_entityName);
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
        $prefix = $this->_sdk->getMapping()->getIdPrefix();
        $entityName = get_class($this->_class);
        $mapping = $this->_sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);        
        $modelName = $this->_sdk->getMapping()->getModelName($key);
        
        $path = (null == $prefix) ? $key : $prefix . '/' . $key;
        $data = $this->_restClient->post($path, $this->_sdk->getSerializer()->serialize($model, $modelName));

        return $this->_client->convert($data, $this->_entityName);
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

        $entityName = get_class($this->_class);
        $mapping = $this->_sdk->getMapping($entityName);
        $key = $mapping->getKeyFromModel($entityName);        
        $prefix = $mapping->getIdPrefix();
        $path = ((null == $prefix) ? $key : $prefix . '/' . $key) . '?';
        
        if ($fieldName != '') {
            $path .= $fieldName .'='. array_shift($arguments);
        } else {
            foreach (array_shift($arguments) as $key=>$value) {
                $path .= strtolower($key) . '=' . $value .'&';
            } 
            $path = rtrim($path, "&");
        }

        $data =  $this->_restClient->get($path);   
        if ($method == 'findOneBy') {
            // If more results are found but one is requested return the first hit.
            if (count($data['hydra:member']) > 1) {
                $data = array_shift($data['hydra:member']);
            }
            return $this->_client->convert($data, $this->_entityName);
        }
        return $this->_client->convertList($data, $this->_entityName);
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return $this->_entityName;
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
        return $this->_class;
    }

}
