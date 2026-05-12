<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Adapter;

use PhpNoobs\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionContext;
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepResult;

/**
 * Adapts the global refactor context to php-rename step execution.
 */
final readonly class RenameServiceAdapter
{
    public const string SERVICE = 'rename';

    /**
     * Constructor.
     *
     * @param PhpRename              $renamer          the rename facade
     * @param RenameDiagnosticMapper $diagnosticMapper the diagnostic mapper
     */
    public function __construct(
        private PhpRename $renamer,
        private RenameDiagnosticMapper $diagnosticMapper = new RenameDiagnosticMapper(),
    ) {
    }

    /**
     * Executes one class FQCN rename step.
     *
     * @param RefactorTransactionContext $context        the current refactor context
     * @param string                     $className      the current class-like FQCN
     * @param string                     $newClassName   the replacement class-like FQCN
     * @param RenameConflictPolicy       $conflictPolicy the rename conflict policy
     */
    public function renameClassFqcn(
        RefactorTransactionContext $context,
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameClassFqcn',
            arguments: [
                'className' => $className,
                'newClassName' => $newClassName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepClassFqcnRename(
                context: $renameContext,
                className: $className,
                newClassName: $newClassName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one method rename step.
     *
     * @param RefactorTransactionContext $context        the current refactor context
     * @param string                     $className      the class name that anchors the method rename
     * @param string                     $methodName     the current method name
     * @param string                     $newMethodName  the replacement method name
     * @param RenameConflictPolicy       $conflictPolicy the rename conflict policy
     */
    public function renameMethod(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'newMethodName' => $newMethodName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepMethodRename(
                context: $renameContext,
                className: $className,
                methodName: $methodName,
                newMethodName: $newMethodName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one short class rename step.
     *
     * @param RefactorTransactionContext $context        the current refactor context
     * @param string                     $className      the current class-like FQCN
     * @param string                     $newClassName   the replacement short class-like name
     * @param RenameConflictPolicy       $conflictPolicy the rename conflict policy
     */
    public function renameClass(
        RefactorTransactionContext $context,
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameClass',
            arguments: [
                'className' => $className,
                'newClassName' => $newClassName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepClassRename(
                context: $renameContext,
                className: $className,
                newClassName: $newClassName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one property rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $className       the class name that anchors the property rename
     * @param string                     $propertyName    the current property name
     * @param string                     $newPropertyName the replacement property name
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameProperty(
        RefactorTransactionContext $context,
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameProperty',
            arguments: [
                'className' => $className,
                'propertyName' => $propertyName,
                'newPropertyName' => $newPropertyName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepPropertyRename(
                context: $renameContext,
                className: $className,
                propertyName: $propertyName,
                newPropertyName: $newPropertyName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one class-constant rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $className       the class name that anchors the class-constant rename
     * @param string                     $constantName    the current class-constant name
     * @param string                     $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameClassConstant(
        RefactorTransactionContext $context,
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameClassConstant',
            arguments: [
                'className' => $className,
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepClassConstantRename(
                context: $renameContext,
                className: $className,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one enum-case rename step.
     *
     * @param RefactorTransactionContext $context        the current refactor context
     * @param string                     $enumName       the enum name that anchors the enum-case rename
     * @param string                     $caseName       the current enum-case name
     * @param string                     $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy       $conflictPolicy the rename conflict policy
     */
    public function renameEnumCase(
        RefactorTransactionContext $context,
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameEnumCase',
            arguments: [
                'enumName' => $enumName,
                'caseName' => $caseName,
                'newCaseName' => $newCaseName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepEnumCaseRename(
                context: $renameContext,
                enumName: $enumName,
                caseName: $caseName,
                newCaseName: $newCaseName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one short function rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $functionName    the current function FQCN
     * @param string                     $newFunctionName the replacement short function name
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameFunction(
        RefactorTransactionContext $context,
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameFunction',
            arguments: [
                'functionName' => $functionName,
                'newFunctionName' => $newFunctionName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepFunctionRename(
                context: $renameContext,
                functionName: $functionName,
                newFunctionName: $newFunctionName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one function FQCN rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $functionName    the current function FQCN
     * @param string                     $newFunctionName the replacement function FQCN
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameFunctionFqcn(
        RefactorTransactionContext $context,
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameFunctionFqcn',
            arguments: [
                'functionName' => $functionName,
                'newFunctionName' => $newFunctionName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepFunctionFqcnRename(
                context: $renameContext,
                functionName: $functionName,
                newFunctionName: $newFunctionName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one short namespace-level constant rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $constantName    the current constant FQCN
     * @param string                     $newConstantName the replacement short constant name
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameConstant(
        RefactorTransactionContext $context,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameConstant',
            arguments: [
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepConstantRename(
                context: $renameContext,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one namespace-level constant FQCN rename step.
     *
     * @param RefactorTransactionContext $context         the current refactor context
     * @param string                     $constantName    the current constant FQCN
     * @param string                     $newConstantName the replacement constant FQCN
     * @param RenameConflictPolicy       $conflictPolicy  the rename conflict policy
     */
    public function renameConstantFqcn(
        RefactorTransactionContext $context,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameConstantFqcn',
            arguments: [
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepConstantFqcnRename(
                context: $renameContext,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one method parameter rename step.
     *
     * @param RefactorTransactionContext $context          the current refactor context
     * @param string                     $className        the method owner FQCN
     * @param string                     $methodName       the method name
     * @param string                     $parameterName    the current parameter name without "$"
     * @param string                     $newParameterName the replacement parameter name without "$"
     * @param int|null                   $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy       $conflictPolicy   the rename conflict policy
     */
    public function renameMethodParameter(
        RefactorTransactionContext $context,
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameMethodParameter',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepMethodParameterRename(
                context: $renameContext,
                className: $className,
                methodName: $methodName,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes one function parameter rename step.
     *
     * @param RefactorTransactionContext $context          the current refactor context
     * @param string                     $functionName     the function FQCN
     * @param string                     $parameterName    the current parameter name without "$"
     * @param string                     $newParameterName the replacement parameter name without "$"
     * @param int|null                   $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy       $conflictPolicy   the rename conflict policy
     */
    public function renameFunctionParameter(
        RefactorTransactionContext $context,
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RefactorTransactionContext {
        return $this->execute(
            context: $context,
            operation: 'renameFunctionParameter',
            arguments: [
                'functionName' => $functionName,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RenameStepContext $renameContext): RenameStepResult => $this->renamer->executeStepFunctionParameterRename(
                context: $renameContext,
                functionName: $functionName,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Executes a rename step and maps it back into the global context.
     *
     * @param RefactorTransactionContext                    $context   the current refactor context
     * @param string                                        $operation the operation name
     * @param array<string, scalar|null>                    $arguments the operation arguments
     * @param callable(RenameStepContext): RenameStepResult $callback  the rename step callback
     */
    private function execute(
        RefactorTransactionContext $context,
        string $operation,
        array $arguments,
        callable $callback,
    ): RefactorTransactionContext {
        $step = $callback($this->renameContext($context));
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
     * Returns the rename step context stored in the global context or creates one.
     *
     * @param RefactorTransactionContext $context the current refactor context
     */
    private function renameContext(RefactorTransactionContext $context): RenameStepContext
    {
        $renameContext = $context->serviceContext(self::SERVICE);

        if ($renameContext instanceof RenameStepContext) {
            return $renameContext;
        }

        return RenameStepContext::fromBuild($context->currentBuild);
    }
}
