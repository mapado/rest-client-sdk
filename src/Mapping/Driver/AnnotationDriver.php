<?php

namespace Mapado\RestClientSdk\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\Reader;
use Mapado\RestClientSdk\Exception\MappingException;
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

/**
 * Class AnnotationDriver
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class AnnotationDriver
{
    /**
     * cachePath
     *
     * @var string
     * @access private
     */
    private $cachePath;

    /**
     * debug
     *
     * @var bool
     * @access private
     */
    private $debug;

    /**
     * Constructor.
     *
     * @param string $cachePath
     * @param bool   $debug
     * @access public
     */
    public function __construct($cachePath, $debug = false)
    {
        $this->cachePath = $cachePath;
        $this->debug = $debug;

        AnnotationRegistry::registerFile(
            __DIR__ . '/../Annotations/AllAnnotations.php'
        );
    }

    /**
     * loadDirectory
     *
     * @param string $path
     * @access public
     * @return ClassMetadata[]
     * @throws MappingException
     */
    public function loadDirectory($path)
    {
        if (!is_dir($path)) {
            throw new MappingException($path . ' is not a valid directory');
        }

        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        $classes = [];
        $includedFiles = [];

        foreach ($iterator as $file) {
            $sourceFile = $file[0];
            if (! preg_match('(^phar:)i', $sourceFile)) {
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
            if ($metadata = $this->getClassMetadataForClassname($class)) {
                $mapping[] = $metadata;
            }
        }

        return $mapping;
    }

    /**
     * loadClassname
     *
     * @param string $classname
     * @access public
     * @return ClassMetadata[]
     */
    public function loadClassname($classname)
    {
        $metadata = $this->getClassMetadataForClassname($classname);

        return $metadata ? [$metadata,] : [];
    }

    /**
     * getClassMetadataForClassname
     *
     * @param string $classname
     * @access private
     * @return ClassMetadata|null
     */
    private function getClassMetadataForClassname($classname)
    {
        $reader = new FileCacheReader(
            new AnnotationReader(),
            $this->cachePath,
            $this->debug
        );

        $reflClass = new \ReflectionClass($classname);
        $classAnnotation = $reader->getClassAnnotation($reflClass, 'Mapado\RestClientSdk\Mapping\Annotations\Entity');

        if (!$classAnnotation) {
            return null;
        }

        $attributeList = [];
        $relationList = [];
        foreach ($reflClass->getProperties() as $property) {
            // manage attributes
            $propertyAnnotation = $this->getPropertyAnnotation($reader, $property, 'Attribute');

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
                $relation = $this->getPropertyAnnotation($reader, $property, 'OneToMany');
                if (!$relation) {
                    $relation = $this->getPropertyAnnotation($reader, $property, 'ManyToOne');
                }

                if ($relation) {
                    $attributeList[] = new Attribute($relation->name, $property->getName());

                    $targetEntity = $relation->targetEntity;
                    if (strpos($targetEntity, '/') === false) {
                        $targetEntity = substr($classname, 0, strrpos($classname, '\\') + 1) . $targetEntity;
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
            $classAnnotation->repository
        );
        $classMetadata->setAttributeList($attributeList);
        $classMetadata->setRelationList($relationList);

        return $classMetadata;
    }

    /**
     * getPropertyAnnotation
     *
     * @param Reader              $reader
     * @param \ReflectionProperty $property
     * @param string              $classname
     * @access private
     * @return null|object
     */
    private function getPropertyAnnotation(
        Reader $reader,
        \ReflectionProperty $property,
        $classname
    ) {
        return $reader->getPropertyAnnotation(
            $property,
            'Mapado\\RestClientSdk\\Mapping\\Annotations\\' . $classname
        );
    }
}
