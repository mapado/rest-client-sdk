<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;

class ObjectRepositoryDynamicReturnTypeExtension implements \PHPStan\Type\DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    /** @var Broker */
    private $broker;

    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    public function getClass(): string
    {
        return 'Mapado\RestClientSdk\EntityRepository';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        return 0 === mb_strpos($methodName, 'findBy')
            || 0 === mb_strpos($methodName, 'findOneBy')
            || 'findAll' === $methodName
            || 'find' === $methodName
            || 'persist' === $methodName;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $calledOnType = $scope->getType($methodCall->var);
        if (!$calledOnType instanceof TypeWithClassName) {
            return new MixedType();
        }

        $methodName = $methodReflection->getName();
        if ($this->broker->hasClass($calledOnType->getClassName())) {
            $repositoryClassReflection = $this->broker->getClass($calledOnType->getClassName());
            if (
                (
                    (
                        0 === mb_strpos($methodName, 'findBy')
                        && mb_strlen($methodName) > mb_strlen('findBy')
                    ) || (
                        0 === mb_strpos($methodName, 'findOneBy')
                        && mb_strlen($methodName) > mb_strlen('findOneBy')
                    )
                )
                && $repositoryClassReflection->hasNativeMethod($methodName)
            ) {
                return ParametersAcceptorSelector::selectFromArgs(
                    $scope,
                    $methodCall->args,
                    $repositoryClassReflection->getNativeMethod($methodName)->getVariants()
                )->getReturnType();
            }
        }

        if (!$calledOnType instanceof ObjectRepositoryType) {
            return new MixedType();
        }

        $entityType = new ObjectType($calledOnType->getEntityClass());

        if ('find' === $methodName || 'persist' === $methodName || 0 === mb_strpos($methodName, 'findOneBy')) {
            return TypeCombinator::addNull($entityType);
        }

        return new CollectionType($entityType);
    }
}
