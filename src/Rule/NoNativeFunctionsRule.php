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

namespace FireHub\PHPStan\Rule;

use PhpParser\ {
    Node, Node\Expr\FuncCall
};
use PHPStan\Analyser\Scope;
use PHPStan\Rules\ {
    Rule, RuleErrorBuilder
};
use PHPStan\Reflection\ReflectionProvider;

/**
 * ### Rule that forbids direct usage of native functions
 * @since 1.0.0
 *
 * @template TNodeType of Node
 *
 * @implements Rule<TNodeType>
 */
final class NoNativeFunctionsRule implements Rule {

    /**
     * ## Constructor
     * @since 1.0.0
     *
     * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider <p>
     * Reflection provider for class hierarchy checks.
     * </p>
     *
     * @return void
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {}

    /**
     * ### Returns the node type this rule is interested in
     * @since 1.0.0
     *
     * @return class-string<TNodeType> All function calls.
     */
    public function getNodeType ():string {

        return FuncCall::class;

    }

    /**
     * ### Process the node
     * @since 1.0.0
     *
     * @param TNodeType $node <p>
     * The node.
     * </p>
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker $scope <p>
     * The scope.
     * </p>
     *
     * @throws \PHPStan\ShouldNotHappenException If error occurs.
     *
     * @return array{\PHPStan\Rules\RuleError} Error messages.
     */
    public function processNode (Node $node, Scope $scope):array {

        $classReflection = $scope->getClassReflection();

        if (
            $classReflection !== null &&
            $classReflection->isSubclassOfClass($this->reflectionProvider
                ->getClass(\FireHub\Core\Support\LowLevel::class))
        ) {
            return [];
        }

        if (!$node instanceof FuncCall) return [];

        $name = $node->name;

        if ($name instanceof Node\Name) {

            $funcName = strtolower((string)$name);

            $list = array_values(
                array_diff(
                    get_defined_functions()['internal'],
                    [
                        'func_num_args',
                        'func_get_arg',
                        'func_get_args',
                    ]
                )
            );

            if (in_array($funcName, $list, true)) {

                return [
                    RuleErrorBuilder::message(sprintf(
                        'Do not use native function %s(), use FireHub LowLevel wrapper instead.',
                        $funcName
                    ))->build()
                ];

            }

        }

        return [];

    }
}