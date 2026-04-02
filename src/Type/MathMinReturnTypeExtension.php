<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026 The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version 7.4
 * @package Core\PHPStan
 *
 * @version GIT: $Id$ Blob checksum.
 */

namespace FireHub\PHPStan\Type;

use FireHub\Core\Support\LowLevel\Math;
use PhpParser\Node\ {
    Arg, Expr\StaticCall
};
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ {
    Constant\ConstantBooleanType, ConstantTypeHelper, DynamicStaticMethodReturnTypeExtension, FloatType,
    IntegerRangeType, IntegerType, MixedType, Type, TypeCombinator
};
use Throwable;

/**
 * ### Extends PHPStan's `min()` function to support constant folding
 * @since 1.0.0
 */
final class MathMinReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension {

    /**
     * ### Returns the class name
     * @since 1.0.0
     *
     * @return class-string Class FQN.
     */
    public function getClass ():string {

        return Math::class;

    }

    /**
     * ### Returns whether the method is supported
     * @since 1.0.0
     *
     * @param \PHPStan\Reflection\MethodReflection $methodReflection <p>
     * The method reflection instance.
     * </p>
     *
     * @return bool True if the method is supported.
     */
    public function isStaticMethodSupported (MethodReflection $methodReflection): bool {

        return $methodReflection->getName() === 'min';

    }

    /**
     * ### Returns the type of the method
     * @since 1.0.0
     *
     * @param \PHPStan\Reflection\MethodReflection $methodReflection <p>
     * The method reflection instance.
     * </p>
     * @param \PhpParser\Node\Expr\StaticCall $methodCall <p>
     * The method call node.
     * </p>
     * @param \PHPStan\Analyser\Scope $scope <p>
     * The current scope.
     * </p>
     *
     * @return \PHPStan\Type\Type The type.
     */
    public function getTypeFromStaticMethodCall (MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope):Type {

        $types = [];

        foreach ($methodCall->args as $arg)
            if ($arg instanceOf Arg)
                $types[] = $scope->getType($arg->value);

        if ($types === []) return new MixedType();

        $values = null;
        foreach ($types as $type) {

            foreach ($type->getConstantScalarValues() as $value)
                $values[] = $value;

        }

        if ($values !== null) {

            try {

                $min = min(...$values);

                return ConstantTypeHelper::getTypeFromValue($min);

            } catch (Throwable) {}

        }

        $result = TypeCombinator::union(...$types);

        if (
            $result->isSuperTypeOf(new IntegerType())->yes()
            || $result->isSuperTypeOf(new FloatType())->yes()
        ) {

            TypeCombinator::remove(
                $result,
                new ConstantBooleanType(false)
            );

            $result = IntegerRangeType::fromInterval(0, null);

        }

        return $result;

    }

}