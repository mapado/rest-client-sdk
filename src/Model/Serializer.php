<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\Mapping\ClassMetadata;

/**
 * Class Serializer
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Serializer
{
    /**
     * mapping
     *
     * @var Mapping
     * @access private
     */
    private $mapping;

    private $sdk;

    /**
     * __construct
     *
     * @param Mapping $mapping
     * @access public
     */
    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * setSdk
     *
     * @param SdkClient $sdk
     * @access public
     * @return self
     */
    public function setSdk(SdkClient $sdk)
    {
        $this->sdk = $sdk;
        return $this;
    }

    /**
     * serialize entity for POST and PUT
     *
     * @param object $entity
     * @access public
     * @return array
     */
    public function serialize($entity, $modelName)
    {
        return $this->recursiveSerialize($entity, $modelName);
    }

    /**
     * deserialize
     *
     * @param array $data
     * @access public
     * @return object
     */
    public function deserialize(array $data, $className)
    {
        // classname may be detected for hydra api with @type key
        $classMetadata = $this->mapping->getClassMetadata($className);

        $instance = new $className();

        foreach ($data as $key => $value) {
            $key = $key === '@id' ? 'id' : $key;
            $setter = 'set' . ucfirst($key);

            if (method_exists($instance, $setter)) {
                $relation = $classMetadata->getRelation($key);
                if ($relation) {
                    if (is_string($value)) {
                        $value = $this->sdk->createProxy($value);
                    } elseif (is_array($value)) {
                        if (isset($value['@id'])) {
                            $key = $this->mapping->getKeyFromId($value['@id']);
                            $subClassMetadata = $this->getClassMetadataFromId($value['@id']);
                            $value = $this->deserialize($value, $subClassMetadata->getModelName());
                        } else {
                            $list = [];
                            foreach ($value as $item) {
                                if (is_string($item)) {
                                    $list[] = $this->sdk->createProxy($item);
                                } elseif (is_array($item) && isset($item['@id'])) {
                                    // cette partie n'est pas encore testée
                                    $key = $this->mapping->getKeyFromId($item['@id']);
                                    $subClassMetadata = $this->getClassMetadataFromId($item['@id']);
                                    $list[] = $this->deserialize($item, $subClassMetadata->getModelName());
                                }
                            }

                            $value = $list;
                        }
                    }
                }

                if (isset($value)) {
                    $attribute = $classMetadata->getAttribute($key);
                    if ($attribute && $attribute->getType() === 'datetime') {
                        $value = new \DateTime($value);
                    }
                    $instance->$setter($value);
                }
            }
        }

        return $instance;
    }

    /**
     * recursiveSerialize
     *
     * @param object $entity
     * @param int $level
     * @access private
     * @return array
     */
    private function recursiveSerialize($entity, $modelName, $level = 0)
    {
        if ($level > 0 && $entity->getId()) {
            return $entity->getId();
        }

        $classMetadata = $this->mapping->getClassMetadata($modelName);

        $attributeList = $classMetadata->getAttributeList();

        $out = [];
        foreach ($attributeList as $attribute) {
            $method = 'get' . ucfirst($attribute->getName());

            if ($attribute->isIdentifier() && !$entity->$method()) {
                continue;
            }
            $relation = $classMetadata->getRelation($attribute->getName());

            $data = $entity->$method();

            if (null === $data && $relation && $relation->isManyToOne()) {
                continue;
            } elseif ($data instanceof \DateTime) {
                $data = $data->format('c');
            } elseif (is_object($data) && $relation && $this->mapping->hasClassMetadata($relation->getTargetEntity())) {
                if ($data->getId()) {
                    $data = $data->getId();
                } elseif ($relation->isManyToOne()) {
                    if ($level > 0) {
                        continue;
                    } else {
                        throw new SdkException('Case not allowed for now');
                    }
                }
            } elseif (is_array($data)) {
                $newData = [];
                foreach ($data as $key => $item) {
                    if ($item instanceof \DateTime) {
                        $newData[$key] = $item->format('c');
                    } elseif (is_object($item) &&
                        $relation &&
                        $this->mapping->hasClassMetadata($relation->getTargetEntity())
                    ) {
                        $newData[$key] = $this->recursiveSerialize($item, $relation->getTargetEntity(), $level + 1);
                    } else {
                        $newData[$key] = $item;
                    }
                }
                $data = $newData;
            }

            $key = $attribute->getName() === 'id' ? '@id' : $attribute->getName();

            $out[$key] = $data;
        }

        return $out;
    }

    /**
     * getClassMetadataFromId
     *
     * @param string $id
     * @access private
     * @return ClassMetadata
     */
    private function getClassMetadataFromId($id)
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);
        return $classMetadata;
    }
}
