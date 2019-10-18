<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\PHPStan\Type;

use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\SdkClientRegistry;

final class ObjectMetadataResolver
{
    /** @var string */
    private $repositoryClass;

    /** @var ?SdkClientRegistry */
    private $registry;

    public function __construct(?string $registryFile)
    {
        if (null !== $registryFile) {
            $this->registry = $this->loadRegistry($registryFile);
        }

        $this->repositoryClass = 'Mapado\RestClientSdk\EntityRepository';
    }

    public function resolveClassnameForKey(string $key): string
    {
        if (null === $this->registry) {
            return $key;
        }

        $metadata = $this->getMetadataForKeyOrClassname($key);

        return $metadata->getModelName();
    }

    public function getRepositoryClass(string $className): string
    {
        if (null === $this->registry) {
            return $this->repositoryClass;
        }

        return $this
            ->registry
            ->getSdkClientForClass($className)
            ->getMapping()
            ->getClassMetadata($className)
            ->getRepositoryName()
        ;
    }

    private function getMetadataForKeyOrClassname(string $value): ClassMetadata
    {
        foreach ($this->registry->getSdkClientList() as $sdkClient) {
            $mapping = $sdkClient->getMapping();

            // get repository by key
            $metadata = $mapping->getClassMetadataByKey($value);

            if ($metadata) {
                return $metadata;
            }

            if ($mapping->hasClassMetadata($value)) {
                return $mapping->getClassMetadata($value);
            }
        }

        throw new \RuntimeException('Unable to find sdk client for key or classname ' . $value);
    }

    private function loadRegistry(string $registryFile): ?SdkClientRegistry
    {
        if (!file_exists($registryFile)
            || !is_readable($registryFile)
        ) {
            throw new \PHPStan\ShouldNotHappenException('Object manager could not be loaded');
        }

        return require $registryFile;
    }
}
