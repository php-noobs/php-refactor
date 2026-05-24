<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Application\Adapter;

use BabelForge\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use BabelForge\PhpRefactor\Domain\Transaction\RefactorTransactionContext;
use BabelForge\PhpRetype\Application\PhpRetype;
use BabelForge\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use BabelForge\PhpRetype\Domain\Retype\Step\RetypeStepResult;
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
     * Executes one class constant type-change step.
     *
     * @param RefactorTransactionContext                                   $context      the current refactor context
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     */
    public function changeClassConstantType(
        RefactorTransactionContext $context,
        string $className,
        string $constantName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClassConstantType',
            arguments: [
                'className' => $className,
                'constantName' => $constantName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClassConstantTypeChange(
                context: $retypeContext,
                className: $className,
                constantName: $constantName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one enum backing type-change step.
     *
     * @param RefactorTransactionContext $context  the current refactor context
     * @param string                     $enumName the enum FQCN
     * @param Identifier                 $typeNode the native PHP backing type node to write
     */
    public function changeEnumBackingType(
        RefactorTransactionContext $context,
        string $enumName,
        Identifier $typeNode,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeEnumBackingType',
            arguments: [
                'enumName' => $enumName,
                'typeNode' => $this->typeNodeLabel($typeNode),
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepEnumBackingTypeChange(
                context: $retypeContext,
                enumName: $enumName,
                typeNode: $typeNode,
            ),
        );
    }

    /**
     * Executes one closure parameter type-change step inside a method.
     *
     * @param RefactorTransactionContext                                   $context        the current refactor context
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInMethod(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureParameterTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureParameterTypeInMethod(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one closure return type-change step inside a method.
     *
     * @param RefactorTransactionContext                                   $context      the current refactor context
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInMethod(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureReturnTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureReturnTypeInMethod(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one arrow function parameter type-change step inside a method.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInMethod(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionParameterTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionParameterTypeInMethod(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                arrowFunctionIndex: $arrowFunctionIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one arrow function return type-change step inside a method.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInMethod(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionReturnTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionReturnTypeInMethod(
                context: $retypeContext,
                className: $className,
                methodName: $methodName,
                arrowFunctionIndex: $arrowFunctionIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one closure parameter type-change step inside a function.
     *
     * @param RefactorTransactionContext                                   $context        the current refactor context
     * @param string                                                       $functionName   the fully-qualified function name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInFunction(
        RefactorTransactionContext $context,
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureParameterTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureParameterTypeInFunction(
                context: $retypeContext,
                functionName: $functionName,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one closure return type-change step inside a function.
     *
     * @param RefactorTransactionContext                                   $context      the current refactor context
     * @param string                                                       $functionName the fully-qualified function name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInFunction(
        RefactorTransactionContext $context,
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureReturnTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureReturnTypeInFunction(
                context: $retypeContext,
                functionName: $functionName,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one arrow function parameter type-change step inside a function.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInFunction(
        RefactorTransactionContext $context,
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionParameterTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionParameterTypeInFunction(
                context: $retypeContext,
                functionName: $functionName,
                arrowFunctionIndex: $arrowFunctionIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one arrow function return type-change step inside a function.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInFunction(
        RefactorTransactionContext $context,
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionReturnTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionReturnTypeInFunction(
                context: $retypeContext,
                functionName: $functionName,
                arrowFunctionIndex: $arrowFunctionIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one closure parameter type-change step inside a file.
     *
     * @param RefactorTransactionContext                                   $context        the current refactor context
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInFile(
        RefactorTransactionContext $context,
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureParameterTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureParameterTypeInFile(
                context: $retypeContext,
                filePath: $filePath,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one closure return type-change step inside a file.
     *
     * @param RefactorTransactionContext                                   $context      the current refactor context
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInFile(
        RefactorTransactionContext $context,
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeClosureReturnTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepClosureReturnTypeInFile(
                context: $retypeContext,
                filePath: $filePath,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Executes one arrow function parameter type-change step inside a file.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInFile(
        RefactorTransactionContext $context,
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionParameterTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionParameterTypeInFile(
                context: $retypeContext,
                filePath: $filePath,
                arrowFunctionIndex: $arrowFunctionIndex,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Executes one arrow function return type-change step inside a file.
     *
     * @param RefactorTransactionContext                                   $context            the current refactor context
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInFile(
        RefactorTransactionContext $context,
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'changeArrowFunctionReturnTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RetypeStepContext $retypeContext): RetypeStepResult => $this->retype->executeStepArrowFunctionReturnTypeInFile(
                context: $retypeContext,
                filePath: $filePath,
                arrowFunctionIndex: $arrowFunctionIndex,
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
