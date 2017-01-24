<?php

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\SdkClient;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

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

    /**
     * @var SdkClient|null
     */
    private $sdk;

    /**
     * Constructor.
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
     * @return Serializer
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
     * @param string $modelName
     * @param array  $context
     * @access public
     * @return array
     */
    public function serialize($entity, $modelName, $context = [])
    {
        return $this->recursiveSerialize($entity, $modelName, 0, $context);
    }

    /**
     * deserialize
     *
     * @param array  $data
     * @param string $className
     * @access public
     * @return object
     */
    public function deserialize(array $data, $className)
    {
        // classname may be detected for hydra api with @type key
        $classMetadata = $this->mapping->getClassMetadata($className);
        $identifierAttribute = $classMetadata->getIdentifierAttribute();
        $identifierAttrKey = $identifierAttribute ? $identifierAttribute->getSerializedKey() : null;

        $instance = new $className();

        foreach ($data as $key => $value) {
            $attribute = $classMetadata->getAttribute($key);
            if (!$attribute) {
                continue;
            }

            $setter = 'set' . ucfirst($attribute->getAttributeName());

            if (method_exists($instance, $setter)) {
                $relation = $classMetadata->getRelation($key);
                if ($relation) {
                    if (is_string($value)) {
                        $value = $this->sdk->createProxy($value);
                    } elseif (is_array($value)) {
                        if (isset($value[$identifierAttrKey])) {
                            $key = $this->mapping->getKeyFromId($value[$identifierAttrKey]);
                            $subClassMetadata = $this->getClassMetadataFromId($value[$identifierAttrKey]);
                            $value = $this->deserialize($value, $subClassMetadata->getModelName());
                        } else {
                            $list = [];
                            foreach ($value as $item) {
                                if (is_string($item)) {
                                    $list[] = $this->sdk->createProxy($item);
                                } elseif (is_array($item) && isset($item[$identifierAttrKey])) {
                                    // not tested for now
                                    // /the $identifierAttrKey is not the real identifier, as it is
                                    // the main object identifier, but we do not have the metadada for now
                                    // the thing we assume now is that every entity "may" have the same key
                                    // as identifier
                                    $key = $this->mapping->getKeyFromId($item[$identifierAttrKey]);
                                    $subClassMetadata = $this->getClassMetadataFromId($item[$identifierAttrKey]);
                                    $list[] = $this->deserialize($item, $subClassMetadata->getModelName());
                                }
                            }

                            $value = $list;
                        }
                    }
                }

                if (isset($value)) {
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
     * @param string $modelName
     * @param int    $level
     * @param array  $context
     * @access private
     * @return array|mixed
     */
    private function recursiveSerialize($entity, $modelName, $level = 0, $context = [])
    {
        if ($level > 0 && empty($context['serializeRelation']) && $entity->getId()) {
            return $entity->getId();
        }

        $classMetadata = $this->mapping->getClassMetadata($modelName);

        $attributeList = $classMetadata->getAttributeList();

        $out = [];
        if (!empty($attributeList)) {
            foreach ($attributeList as $attribute) {
                $method = 'get' . ucfirst($attribute->getAttributeName());

                if ($attribute->isIdentifier() && !$entity->$method()) {
                    continue;
                }
                $relation = $classMetadata->getRelation($attribute->getSerializedKey());

                $data = $entity->$method();

                if (null === $data && $relation && $relation->isManyToOne() && $level > 0) {
                    /*
                        We only serialize the root many-to-one relations to prevent, hopefully,
                        unlinked and/or duplicated content. For instance, a cart with cartItemList containing
                        null values for the cart [{ cart => null, ... }] may lead the creation of
                        CartItem entities explicitly bound to a null Cart instead of the created/updated Cart.
                     */
                    continue;
                } elseif ($data instanceof \DateTime) {
                    $data = $data->format('c');
                } elseif (is_object($data) && get_class($data) === "libphonenumber\PhoneNumber") {
                    $phoneNumberUtil = PhoneNumberUtil::getInstance();
                    $data = $phoneNumberUtil->format(
                        $data,
                        PhoneNumberFormat::INTERNATIONAL
                    );
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
                            $serializeRelation = !empty($context['serializeRelations'])
                                && in_array($relation->getSerializedKey(), $context['serializeRelations']);

                            $newData[$key] = $this->recursiveSerialize(
                                $item,
                                $relation->getTargetEntity(),
                                $level + 1,
                                [ 'serializeRelation' => $serializeRelation ]
                            );
                        } else {
                            $newData[$key] = $item;
                        }
                    }
                    $data = $newData;
                }

                $key = $attribute->getSerializedKey();

                $out[$key] = $data;
            }
        }

        return $out;
    }

    /**
     * getClassMetadataFromId
     *
     * @param string $id
     * @access private
     * @return ClassMetadata|null
     */
    private function getClassMetadataFromId($id)
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);
        return $classMetadata;
    }
}
