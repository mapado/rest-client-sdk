<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

class RepositoryMagicFindExtension implements MethodsClassReflectionExtension
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function hasMethod(
        ClassReflection $classReflection,
        string $methodName,
    ): bool {
        if (
            0 !== mb_strpos($methodName, 'findBy') &&
            0 !== mb_strpos($methodName, 'findOneBy')
        ) {
            return false;
        }
        if (
            'Mapado\RestClientSdk\EntityRepository' ===
            $classReflection->getName()
        ) {
            return true;
        }
        if (
            !$this->reflectionProvider->hasClass(
                'Mapado\RestClientSdk\EntityRepository',
            )
        ) {
            return false;
        }

        return $classReflection->isSubclassOfClass(
            $this->reflectionProvider->getClass(
                'Mapado\RestClientSdk\EntityRepository',
            ),
        );
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName,
    ): MethodReflection {
        /** @phpstan-ignore-next-line phpstanApi.constructor */
        return new DummyMethodReflection($methodName);
    }
}
