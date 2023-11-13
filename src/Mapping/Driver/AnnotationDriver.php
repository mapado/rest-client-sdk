<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\Reader;
use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\Annotations;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * Class AnnotationDriver
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class AnnotationDriver
{
    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(string $cachePath, bool $debug = false)
    {
        $this->cachePath = $cachePath;
        $this->debug = $debug;

        AnnotationRegistry::registerFile(
            __DIR__ . '/../Annotations/AllAnnotations.php'
        );
    }

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

        /** @var array<int, array|string> $iterator */
        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        $classes = [];
        $includedFiles = [];

        foreach ($iterator as $file) {
            $sourceFile = $file[0];
            if (!preg_match('(^phar:)i', $sourceFile)) {
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
        string $classname
    ): ?ClassMetadata {
        $reader = new FileCacheReader(
            new AnnotationReader(),
            $this->cachePath,
            $this->debug
        );

        $reflClass = new \ReflectionClass($classname);
        /** @var Annotations\Entity|null */
        $classAnnotation = $reader->getClassAnnotation(
            $reflClass,
            Annotations\Entity::class
        );

        if (!$classAnnotation) {
            return null;
        }

        $attributeList = [];
        $relationList = [];
        foreach ($reflClass->getProperties() as $property) {
            // manage attributes
            /** @var Annotations\Attribute|null */
            $propertyAnnotation = $this->getPropertyAnnotation(
                $reader,
                $property,
                'Attribute'
            );

            if ($propertyAnnotation) {
                $isId = $this->getPropertyAnnotation($reader, $property, 'Id');

                $attributeList[] = new Attribute(
                    $propertyAnnotation->name,
                    $property->getName(),
                    $propertyAnnotation->type,
                    (bool) $isId
                );
            } else {
                // manage relations
                /** @var Annotations\OneToMany|null */
                $relation = $this->getPropertyAnnotation(
                    $reader,
                    $property,
                    'OneToMany'
                );
                if (!$relation) {
                    /** @var Annotations\ManyToOne|null */
                    $relation = $this->getPropertyAnnotation(
                        $reader,
                        $property,
                        'ManyToOne'
                    );
                }

                if ($relation) {
                    $attributeList[] = new Attribute(
                        $relation->name,
                        $property->getName()
                    );

                    $targetEntity = $relation->targetEntity;
                    if (false === mb_strpos($targetEntity, '/')) {
                        $targetEntity =
                            mb_substr(
                                $classname,
                                0,
                                mb_strrpos($classname, '\\') + 1
                            ) . $targetEntity;
                    }

                    $relationList[] = new Relation(
                        $relation->name,
                        $relation->type,
                        $targetEntity
                    );
                }
            }
        }

        $classMetadata = new ClassMetadata(
            $classAnnotation->key,
            $classname,
            $classAnnotation->getRepository()
        );
        $classMetadata->setAttributeList($attributeList);
        $classMetadata->setRelationList($relationList);

        return $classMetadata;
    }

    /**
     * @return object|null the Annotation or NULL, if the requested annotation does not exist
     */
    private function getPropertyAnnotation(
        Reader $reader,
        \ReflectionProperty $property,
        string $classname
    ): ?object {
        /** @var class-string $classname */
        $classname =
            'Mapado\\RestClientSdk\\Mapping\\Annotations\\' . $classname;

        return $reader->getPropertyAnnotation($property, $classname);
    }
}
