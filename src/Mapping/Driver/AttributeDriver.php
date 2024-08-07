<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Driver;

use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\Attributes;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * Class AttributeDriver
 */
class AttributeDriver
{
    /**
     * @return array<ClassMetadata>
     *
     * @throws MappingException
     */
    public function loadDirectory(string $path): array
    {
        if (!is_dir($path)) {
            throw new MappingException($path . ' is not a valid directory');
        }

        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::SKIP_DOTS,
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH,
        );

        $classes = [];
        $includedFiles = [];

        foreach ($iterator as $file) {
            $sourceFile = is_array($file) ? $file[0] : null;
            if (
                is_string($sourceFile)
                && !preg_match('(^phar:)i', $sourceFile)
            ) {
                $sourceFile = realpath($sourceFile);
            }

            require_once $sourceFile;
            $includedFiles[] = $sourceFile;
        }

        $declared = get_declared_classes();
        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles)) {
                $classes[] = $className;
            }
        }

        $mapping = [];
        foreach ($classes as $class) {
            $metadata = $this->getClassMetadataForClassname($class);
            if ($metadata) {
                $mapping[] = $metadata;
            }
        }

        return $mapping;
    }

    /**
     * @param class-string $classname
     *
     * @return array<ClassMetadata>
     */
    public function loadClassname(string $classname): array
    {
        $metadata = $this->getClassMetadataForClassname($classname);

        return $metadata ? [$metadata] : [];
    }

    /**
     * @param class-string $classname
     *
     * @throws \ReflectionException
     */
    private function getClassMetadataForClassname(
        string $classname,
    ): ?ClassMetadata {
        $reflClass = new \ReflectionClass($classname);
        $classAttribute = $this->getClassAttribute(
            $reflClass,
            Attributes\Entity::class,
        );

        if (!$classAttribute) {
            return null;
        }

        $attributeList = [];
        $relationList = [];
        foreach ($reflClass->getProperties() as $property) {
            // manage attributes
            $propertyAttribute = $this->getPropertyAttribute(
                $property,
                Attributes\Attribute::class,
            );

            if ($propertyAttribute) {
                $propertyIdAttribute = $this->getPropertyAttribute(
                    $property,
                    Attributes\Id::class,
                );

                $attributeList[] = new Attribute(
                    $propertyAttribute->name,
                    $property->getName(),
                    $propertyAttribute->type,
                    $propertyIdAttribute instanceof Attributes\Id,
                );
            } else {
                $relation = $this->getPropertyAttribute(
                    $property,
                    Attributes\OneToMany::class,
                );
                if (!$relation) {
                    $relation = $this->getPropertyAttribute(
                        $property,
                        Attributes\ManyToOne::class,
                    );
                }

                if ($relation) {
                    $attributeList[] = new Attribute(
                        $relation->name,
                        $property->getName(),
                    );

                    $targetEntity = $relation->targetEntity;
                    if (false === mb_strpos($targetEntity, '/')) {
                        $targetEntity =
                            mb_substr(
                                $classname,
                                0,
                                mb_strrpos($classname, '\\') + 1,
                            ) . $targetEntity;
                    }

                    $relationList[] = new Relation(
                        $relation->name,
                        $relation->type,
                        $targetEntity,
                    );
                }
            }
        }

        $classMetadata = new ClassMetadata(
            $classAttribute->key,
            $classname,
            $classAttribute->repository,
        );
        $classMetadata->setAttributeList($attributeList);
        $classMetadata->setRelationList($relationList);

        return $classMetadata;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $classname
     *
     * @return new<T>|null
     */
    private function getPropertyAttribute(
        \ReflectionProperty $property,
        string $classname,
    ) {
        return $this->getAttribute($property, $classname);
    }

    /**
     * @template T of Attributes\Entity
     *
     * @param class-string<T> $classname
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return new<T>|null
     */
    private function getClassAttribute(
        \ReflectionClass $reflectionClass,
        string $classname,
    ) {
        return $this->getAttribute($reflectionClass, $classname);
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<object>|\ReflectionProperty $reflection
     * @param class-string<T> $classname
     *
     * @return new<T>|null
     */
    private function getAttribute(
        \ReflectionClass|\ReflectionProperty $reflection,
        string $classname,
    ) {
        $attribute =
            $reflection->getAttributes(
                $classname,
                \ReflectionAttribute::IS_INSTANCEOF,
            )[0] ?? null;

        if (!$attribute instanceof \ReflectionAttribute) {
            return null;
        }

        return $attribute->newInstance();
    }
}
