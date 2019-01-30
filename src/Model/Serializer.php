<?php

namespace Mapado\RestClientSdk\Model;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\UnitOfWork;

/**
 * Class Serializer
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Serializer
{
    /**
     * mapping
     *
     * @var Mapping
     */
    private $mapping;

    /**
     * @var SdkClient|null
     */
    private $sdk;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * Constructor.
     *
     * @param Mapping $mapping
     */
    public function __construct(Mapping $mapping, UnitOfWork $unitOfWork)
    {
        $this->mapping = $mapping;
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * setSdk
     *
     * @param SdkClient $sdk
     *
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
     *
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
     *
     * @return object
     */
    public function deserialize(array $data, $className)
    {
        $className = $this->resolveRealClassName($data, $className);

        $classMetadata = $this->mapping->getClassMetadata($className);

        $attributeList = $classMetadata->getAttributeList();

        $instance = new $className();

        foreach ($attributeList as $attribute) {
            $key = $attribute->getSerializedKey();

            if (!ArrayHelper::arrayHas($data, $key)) {
                continue;
            }

            $value = ArrayHelper::arrayGet($data, $key);

            $setter = 'set' . ucfirst($attribute->getAttributeName());

            if (method_exists($instance, $setter)) {
                $relation = $classMetadata->getRelation($key);
                if ($relation) {
                    if (is_string($value)) {
                        $value = $this->sdk->createProxy($value);
                    } elseif (is_array($value)) {
                        $targetEntity = $relation->getTargetEntity();
                        $relationClassMetadata = $this->mapping->getClassMetadata(
                            $targetEntity
                        );

                        if ($relation->isManyToOne()) {
                            $value = $this->deserialize(
                                $value,
                                $relationClassMetadata->getModelName()
                            );
                        } else {
                            // One-To-Many association
                            $list = [];
                            foreach ($value as $item) {
                                if (is_string($item)) {
                                    $list[] = $this->sdk->createProxy($item);
                                } elseif (is_array($item)) {
                                    $list[] = $this->deserialize(
                                        $item,
                                        $relationClassMetadata->getModelName()
                                    );
                                }
                            }

                            $value = $list;
                        }
                    }
                }

                if (isset($value)) {
                    if ('datetime' === $attribute->getType()) {
                        $value = new \DateTime($value);
                    }

                    $instance->{$setter}($value);
                }
            }
        }

        $classMetadata = $this->getClassMetadata($instance);
        if ($classMetadata->hasIdentifierAttribute()) {
            $idGetter = $classMetadata->getIdGetter();

            if ($idGetter) {
                $callable = [$instance, $idGetter];
                $identifier = is_callable($callable)
                    ? call_user_func($callable)
                    : null;

                if ($identifier) {
                    $this->unitOfWork->registerClean($identifier, $instance);
                }
            }
        }

        return $instance;
    }

    /**
     * If provided class name is abstract (a base class), the real class name (child class)
     * may be available in some data fields.
     *
     * @param array  $data
     * @param string $className
     *
     * @return string
     */
    private function resolveRealClassName(array $data, $className)
    {
        if (!empty($data['@id'])) {
            $classMetadata = $this->mapping->tryGetClassMetadataById(
                $data['@id']
            );

            if ($classMetadata) {
                return $classMetadata->getModelName();
            }
        }

        // Real class name could also be retrieved from @type property.
        return $className;
    }

    /**
     * recursiveSerialize
     *
     * @param object $entity
     * @param string $modelName
     * @param int    $level
     * @param array  $context
     *
     * @return array|mixed
     */
    private function recursiveSerialize(
        $entity,
        $modelName,
        $level = 0,
        $context = []
    ) {
        $classMetadata = $this->mapping->getClassMetadata($modelName);

        if ($level > 0 && empty($context['serializeRelation'])) {
            if ($classMetadata->hasIdentifierAttribute()) {
                $idAttribute = $classMetadata->getIdentifierAttribute();
                $getter = 'get' . ucfirst($idAttribute->getAttributeName());
                $tmpId = $entity->{$getter}();
                if ($tmpId) {
                    return $tmpId;
                }
            }
        }

        $attributeList = $classMetadata->getAttributeList();

        $out = [];
        if (!empty($attributeList)) {
            foreach ($attributeList as $attribute) {
                $method = 'get' . ucfirst($attribute->getAttributeName());

                if ($attribute->isIdentifier() && !$entity->{$method}()) {
                    continue;
                }
                $relation = $classMetadata->getRelation(
                    $attribute->getSerializedKey()
                );

                $data = $entity->{$method}();

                if (
                    null === $data &&
                    $relation &&
                    $relation->isManyToOne() &&
                    $level > 0
                ) {
                    /*
                        We only serialize the root many-to-one relations to prevent, hopefully,
                        unlinked and/or duplicated content. For instance, a cart with cartItemList containing
                        null values for the cart [{ cart => null, ... }] may lead the creation of
                        CartItem entities explicitly bound to a null Cart instead of the created/updated Cart.
                     */
                    continue;
                } elseif ($data instanceof \DateTime) {
                    $data = $data->format('c');
                } elseif (is_object($data) && $data instanceof PhoneNumber) {
                    $phoneNumberUtil = PhoneNumberUtil::getInstance();
                    $data = $phoneNumberUtil->format(
                        $data,
                        PhoneNumberFormat::INTERNATIONAL
                    );
                } elseif (
                    is_object($data) &&
                    $relation &&
                    $this->mapping->hasClassMetadata(
                        $relation->getTargetEntity()
                    )
                ) {
                    $relationClassMetadata = $this->mapping->getClassMetadata(
                        $relation->getTargetEntity()
                    );

                    if (!$relationClassMetadata->hasIdentifierAttribute()) {
                        $data = $this->recursiveSerialize(
                            $data,
                            $relation->getTargetEntity(),
                            $level + 1,
                            $context
                        );
                    } else {
                        $idAttribute = $relationClassMetadata->getIdentifierAttribute();
                        $idGetter =
                            'get' . ucfirst($idAttribute->getAttributeName());

                        if (
                            method_exists($data, $idGetter) &&
                            $data->{$idGetter}()
                        ) {
                            $data = $data->{$idGetter}();
                        } elseif ($relation->isManyToOne()) {
                            if ($level > 0) {
                                continue;
                            } else {
                                throw new SdkException(
                                    'Case not allowed for now'
                                );
                            }
                        }
                    }
                } elseif (is_array($data)) {
                    $newData = [];
                    foreach ($data as $key => $item) {
                        if ($item instanceof \DateTime) {
                            $newData[$key] = $item->format('c');
                        } elseif (
                            is_object($item) &&
                            $relation &&
                            $this->mapping->hasClassMetadata(
                                $relation->getTargetEntity()
                            )
                        ) {
                            $serializeRelation =
                                !empty($context['serializeRelations']) &&
                                in_array(
                                    $relation->getSerializedKey(),
                                    $context['serializeRelations']
                                );

                            $newData[$key] = $this->recursiveSerialize(
                                $item,
                                $relation->getTargetEntity(),
                                $level + 1,
                                ['serializeRelation' => $serializeRelation]
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
     *
     * @return ClassMetadata|null
     */
    private function getClassMetadataFromId($id)
    {
        $key = $this->mapping->getKeyFromId($id);
        $classMetadata = $this->mapping->getClassMetadataByKey($key);

        return $classMetadata;
    }

    private function getClassMetadata($entity)
    {
        return $this->mapping->getClassMetadata(get_class($entity));
    }
}
