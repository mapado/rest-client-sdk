<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class GetRepositoryDynamicReturnTypeExtension implements
    DynamicMethodReturnTypeExtension
{
    private string $sdkClientClass;

    private ObjectMetadataResolver $metadataResolver;

    public function __construct(
        string $sdkClientClass,
        ObjectMetadataResolver $metadataResolver,
    ) {
        /** @var class-string $sdkClientClass */
        $this->sdkClientClass = $sdkClientClass;
        $this->metadataResolver = $metadataResolver;
    }

    public function getClass(): string
    {
        /** @var class-string */
        return $this->sdkClientClass;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'getRepository' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();
        if (0 === count($args)) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $args,
                $methodReflection->getVariants(),
            )->getReturnType();
        }
        $argType = $scope->getType($args[0]->value);
        $constantStrings = $argType->getConstantStrings();
        if (count($constantStrings) === 0) {
            return new MixedType();
        }
        $objectName = $constantStrings[0]->getValue();
        $className = $this->metadataResolver->resolveClassnameForKey(
            $objectName,
        );
        $repositoryClass = $this->metadataResolver->getRepositoryClass(
            $className,
        );

        return new ObjectRepositoryType($className, $repositoryClass);
    }
}
