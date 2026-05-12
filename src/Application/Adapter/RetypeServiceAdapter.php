<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Adapter;

use PhpNoobs\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionContext;
use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepResult;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Adapts the global refactor context to php-retype step execution.
 */
final readonly class RetypeServiceAdapter
{
    public const string SERVICE = 'retype';

    /**
     * Constructor.
     *
     * @param PhpRetype              $retype           the retype facade
     * @param RetypeDiagnosticMapper $diagnosticMapper the diagnostic mapper
     */
    public function __construct(
        private PhpRetype $retype,
        private RetypeDiagnosticMapper $diagnosticMapper = new RetypeDiagnosticMapper(),
    ) {
    }

    /**
     * Executes one method parameter type-change step.
     *
     * @param RefactorTransactionContext                                   $context        the current refactor context
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     */
    public function changeMethodParameterType(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeMethodParameterType',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepMethodParameterTypeChange(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one function parameter type-change step.
     *
     * @param RefactorTransactionContext                                   $context        the current refactor context
     * @param string                                                       $functionName   the function FQCN
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     */
    public function changeFunctionParameterType(
        RefactorTransactionContext $context,
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeFunctionParameterType',
            arguments: [
                'functionName' => $functionName,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepFunctionParameterTypeChange(
                context: $retypeContext,
                functionName: $functionName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one method return type-change step.
     *
     * @param RefactorTransactionContext                                   $context    the current refactor context
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     */
    public function changeMethodReturnType(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeMethodReturnType',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepMethodReturnTypeChange(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one function return type-change step.
     *
     * @param RefactorTransactionContext                                   $context      the current refactor context
     * @param string                                                       $functionName the function FQCN
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeFunctionReturnType(
        RefactorTransactionContext $context,
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeFunctionReturnType',
            arguments: [
                'functionName' => $functionName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepFunctionReturnTypeChange(
                context: $retypeContext,
                functionName: $functionName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one property type-change step.
     *
     * @param RefactorTransactionContext                                   $context       the current refactor context
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     */
    public function changePropertyType(
        RefactorTransactionContext $context,
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changePropertyType',
            arguments: [
                'className' => $className,
                'propertyNames' => $this->propertyNamesLabel($propertyNames),
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepPropertyTypeChange(
                context: $retypeContext,
                className: $className,
                propertyNames: $propertyNames,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes a retype step and maps it back into the global context.
     *
     * @param RefactorTransactionContext                    $context   the current refactor context
     * @param string                                        $operation the operation name
     * @param array<string, scalar|null>                    $arguments the operation arguments
     * @param callable(RetypeStepContext): RetypeStepResult $callback  the retype step callback
     */
    private function execute(
        RefactorTransactionContext $context,
        string $operation,
        array $arguments,
        callable $callback,
    ): RefactorTransactionContext {
        $step = $callback($this->retypeContext($context));
        $diagnostics = $this->diagnosticMapper->map($step->diagnostics);
        $entry = new RefactorActionJournalEntry(
            service: self::SERVICE,
            operation: $operation,
            arguments: $arguments,
            applied: $step->applied,
            diagnostics: $diagnostics,
            touchedFiles: $step->touchedFiles,
        );

        return $context
            ->withCurrentBuild($step->context->currentBuild)
            ->withServiceContext(self::SERVICE, $step->context)
            ->withActionResult($diagnostics, $step->touchedFiles, $entry);
    }

    /**
     * Returns the retype step context stored in the global context or creates one.
     *
     * @param RefactorTransactionContext $context the current refactor context
     */
    private function retypeContext(RefactorTransactionContext $context): RetypeStepContext
    {
        $retypeContext = $context->serviceContext(self::SERVICE);

        if ($retypeContext instanceof RetypeStepContext) {
            return $retypeContext;
        }

        return RetypeStepContext::fromBuild($context->currentBuild);
    }

    /**
     * Returns a stable journal label for a PHP-Parser type node.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native PHP type node
     */
    private function typeNodeLabel(Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode): ?string
    {
        if (null === $typeNode) {
            return null;
        }

        return $typeNode::class;
    }

    /**
     * Returns a stable journal label for one or more property names.
     *
     * @param string|list<string> $propertyNames the property name or property names without "$"
     */
    private function propertyNamesLabel(string|array $propertyNames): string
    {
        if (is_string($propertyNames)) {
            return $propertyNames;
        }

        return implode(',', $propertyNames);
    }
}
