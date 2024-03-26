<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Model;

use DateTimeImmutable;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mapado\RestClientSdk\Exception\MissingSetterException;
use Mapado\RestClientSdk\Exception\SdkException;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\UnitOfWork;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * @var SdkClient
     */
    private $sdk;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(Mapping $mapping, UnitOfWork $unitOfWork)
    {
        $this->mapping = $mapping;
        $this->unitOfWork = $unitOfWork;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @required
     */
    public function setSdk(SdkClient $sdk): self
    {
        $this->sdk = $sdk;

        return $this;
    }

    /**
     * serialize entity for POST and PUT
     */
    public function serialize(
        object $entity,
        string $modelName,
        array $context = [],
    ): array {
        $out = $this->recursiveSerialize($entity, $modelName, 0, $context);

        if (is_string($out)) {
            throw new \RuntimeException(
                'recursiveSerialize should return an array for level 0 of serialization. This should not happen.',
            );
        }

        return $out;
    }

    /**
     * @param class-string $className
     */
    public function deserialize(array $data, string $className): object
    {
        $className = $this->resolveRealClassName($data, $className);

        $classMetadata = $this->mapping->getClassMetadata($className);

        $attributeList = $classMetadata->getAttributeList();

        $instance = new $className();

        if (!is_object($instance)) {
            throw new \RuntimeException(
                "The class $className is not instantiable",
            );
        }

        if ($attributeList) {
            foreach ($attributeList as $attribute) {
                $key = $attribute->getSerializedKey();

                if (!ArrayHelper::arrayHas($data, $key)) {
                    continue;
                }

                $value = ArrayHelper::arrayGet($data, $key);

                $attributeName = $attribute->getAttributeName();
                $this->throwIfAttributeIsNotWritable($instance, $attributeName);

                $relation = $classMetadata->getRelation($key);
                if ($relation) {
                    if (is_string($value)) {
                        $value = $this->sdk->createProxy($value);
                    } elseif (is_array($value)) {
                        $targetEntity = $relation->getTargetEntity();
                        $relationClassMetadata = $this->mapping->getClassMetadata(
                            $targetEntity,
                        );

                        if ($relation->isManyToOne()) {
                            $value = $this->deserialize(
                                $value,
                                $relationClassMetadata->getModelName(),
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
                                        $relationClassMetadata->getModelName(),
                                    );
                                }
                            }

                            $value = $list;
                        }
                    }
                }

                if (isset($value)) {
                    if ('datetime' === $attribute->getType()) {
                        if (!is_string($value)) {
                            throw new \RuntimeException(
                                "The value for $attributeName to cast to datetime value should be a string",
                            );
                        }

                        $this->setDateTimeValue(
                            $instance,
                            $attributeName,
                            $value,
                        );
                    } else {
                        $this->propertyAccessor->setValue(
                            $instance,
                            $attributeName,
                            $value,
                        );
                    }
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
                    $this->unitOfWork->registerClean(
                        (string) $identifier,
                        $instance,
                    );
                }
            }
        }

        return $instance;
    }

    /**
     * If provided class name is abstract (a base class), the real class name (child class)
     * may be available in some data fields.
     */
    private function resolveRealClassName(
        array $data,
        string $className,
    ): string {
        if (!empty($data['@id'])) {
            $classMetadata = $this->mapping->tryGetClassMetadataById(
                $data['@id'],
            );

            if ($classMetadata) {
                return $classMetadata->getModelName();
            }
        }

        // Real class name could also be retrieved from @type property.
        return $className;
    }

    /**
     * @return array|string
     */
    private function recursiveSerialize(
        object $entity,
        string $modelName,
        int $level = 0,
        array $context = [],
    ) {
        $classMetadata = $this->mapping->getClassMetadata($modelName);

        if ($level > 0 && empty($context['serializeRelation'])) {
            if ($classMetadata->hasIdentifierAttribute()) {
                $tmpId = $entity->{$classMetadata->getIdGetter()}();
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
                    $attribute->getSerializedKey(),
                );

                $data = $entity->{$method}();

                if (
                    null === $data
                    && $relation
                    && $relation->isManyToOne()
                    && $level > 0
                ) {
                    /*
                        We only serialize the root many-to-one relations to prevent, hopefully,
                        unlinked and/or duplicated content. For instance, a cart with cartItemList containing
                        null values for the cart [{ cart => null, ... }] may lead the creation of
                        CartItem entities explicitly bound to a null Cart instead of the created/updated Cart.
                     */
                    continue;
                } elseif ($data instanceof \DateTimeInterface) {
                    $data = $data->format('c');
                } elseif (is_object($data) && $data instanceof PhoneNumber) {
                    $phoneNumberUtil = PhoneNumberUtil::getInstance();
                    $data = $phoneNumberUtil->format(
                        $data,
                        PhoneNumberFormat::INTERNATIONAL,
                    );
                } elseif (
                    is_object($data)
                    && $relation
                    && $this->mapping->hasClassMetadata(
                        $relation->getTargetEntity(),
                    )
                ) {
                    $relationClassMetadata = $this->mapping->getClassMetadata(
                        $relation->getTargetEntity(),
                    );

                    if (!$relationClassMetadata->hasIdentifierAttribute()) {
                        $data = $this->recursiveSerialize(
                            $data,
                            $relation->getTargetEntity(),
                            $level + 1,
                            $context,
                        );
                    } else {
                        $idAttribute = $relationClassMetadata->getIdentifierAttribute();
                        $idGetter =
                            'get' . ucfirst($idAttribute->getAttributeName());

                        if (
                            method_exists($data, $idGetter)
                            && $data->{$idGetter}()
                        ) {
                            $data = $data->{$idGetter}();
                        } elseif ($relation->isManyToOne()) {
                            if ($level > 0) {
                                continue;
                            } else {
                                throw new SdkException(
                                    'Case not allowed for now',
                                );
                            }
                        }
                    }
                } elseif (is_array($data)) {
                    $newData = [];
                    foreach ($data as $key => $item) {
                        if ($item instanceof \DateTimeInterface) {
                            $newData[$key] = $item->format('c');
                        } elseif (
                            is_object($item)
                            && $relation
                            && $this->mapping->hasClassMetadata(
                                $relation->getTargetEntity(),
                            )
                        ) {
                            $serializeRelation =
                                !empty($context['serializeRelations'])
                                && in_array(
                                    $relation->getSerializedKey(),
                                    $context['serializeRelations'],
                                );

                            $newData[$key] = $this->recursiveSerialize(
                                $item,
                                $relation->getTargetEntity(),
                                $level + 1,
                                ['serializeRelation' => $serializeRelation],
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

    private function getClassMetadataFromId(string $id): ?ClassMetadata
    {
        $key = $this->mapping->getKeyFromId($id);

        return $this->mapping->getClassMetadataByKey($key);
    }

    private function getClassMetadata(object $entity): ClassMetadata
    {
        return $this->mapping->getClassMetadata($entity::class);
    }

    private function throwIfAttributeIsNotWritable(
        object $instance,
        string $attribute,
    ): void {
        if (!$this->propertyAccessor->isWritable($instance, $attribute)) {
            throw new MissingSetterException(
                sprintf(
                    'Property %s is not writable for class %s. Please make it writable. You can check the property-access documentation here : https://symfony.com/doc/current/components/property_access.html#writing-to-objects',
                    $attribute,
                    $instance::class,
                ),
            );
        }
    }

    private function setDateTimeValue(
        object $instance,
        string $attributeName,
        string $value,
    ): void {
        try {
            $this->propertyAccessor->setValue(
                $instance,
                $attributeName,
                new \DateTime($value),
            );
        } catch (InvalidArgumentException $e) {
            if (
                false ===
                    mb_strpos(
                        $e->getMessage(),
                        'Expected argument of type "DateTimeImmutable", "DateTime" given', // symfony < 4.4 message
                    )
                && false ===
                    mb_strpos(
                        $e->getMessage(),
                        'Expected argument of type "DateTimeImmutable", "instance of DateTime" given', // symfony >= 4.4 message
                    )
            ) {
                // not an issue with DateTimeImmutable, then rethrow exception
                throw $e;
            }

            // The excepted value is a DateTimeImmutable, so let's do that
            $this->propertyAccessor->setValue(
                $instance,
                $attributeName,
                new \DateTimeImmutable($value),
            );
        }
    }
}
