<?php
namespace Mapado\RestClientSdk;

class EntityRepository
{
    /**
     * @object REST Client
     */
    protected $restClient;
    
    /**
     *
     * @object The client for processing 
     */
    protected $client;

    /**
     * @object The Repository to be used
     */
    protected $_class;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @param $restClient   The Client to use for connections to the API.
     * @param $class        The class descriptor.
     */
    public function __construct($client, $restClient, $class)
    {
        $this->client      = $client;
        $this->restClient  = $restClient;
        $this->_class      = $class;
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
        $id = $this->client->convertId($id, get_class($this->_class));
        return $this->restClient->get($id);
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
        $prefix = $mapping->getIdPrefix();
        $key = $mapping->getKeyFromClientName(get_called_class());
        $data = $this->restClient->get($prefix . '/' . $key);

        return $this->convertList($data);
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

        return $this->deserialize($data, $modelName);
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
        $modelName = $this->sdk->getMapping()->getModelName($key);

        $path = $prefix . '/' . $key;
        $data = $this->restClient->post($path, $this->sdk->getSerializer()->serialize($model, $modelName));

        $modelName = $this->sdk->getMapping()->getModelName($key);

        return $this->deserialize($data, $modelName);
    } 
    
    
    
    /**
     * ORIGINAL BELOW HERE
     */


    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return array|object The found entity/entities.
     *
     * @throws ORMException
     * @throws \BadMethodCallException If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = substr($method, 6);
                $method = 'findBy';
                break;

            case (0 === strpos($method, 'findOneBy')):
                $by = substr($method, 9);
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

        $fieldName = lcfirst(\Doctrine\Common\Util\Inflector::classify($by));

        if ($this->_class->hasField($fieldName) || $this->_class->hasAssociation($fieldName)) {
            switch (count($arguments)) {
                case 1:
                    return $this->$method(array($fieldName => $arguments[0]));

                case 2:
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1]);

                case 3:
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2]);

                case 4:
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2], $arguments[3]);

                default:
                    // Do nothing
            }
        }

        throw ORMException::invalidFindByCall($this->_entityName, $fieldName, $method.$by);
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
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    protected function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function matching(Criteria $criteria)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }
}
