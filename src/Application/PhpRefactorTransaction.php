<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRefactor\Application\Adapter\RenameServiceAdapter;
use PhpNoobs\PhpRefactor\Application\Adapter\RetypeServiceAdapter;
use PhpNoobs\PhpRefactor\Application\Snapshot\VirtualFileSnapshotCollection;
use PhpNoobs\PhpRefactor\Application\Snapshot\VirtualFileSnapshotter;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnostic;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticSeverity;
use PhpNoobs\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionContext;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionResult;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionStatus;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Coordinates one global multi-service refactor transaction.
 */
final class PhpRefactorTransaction
{
    /**
     * Constructor.
     *
     * @param RefactorTransactionContext    $context              the current transaction context
     * @param VirtualFileSnapshotCollection $snapshots            the global begin-transaction snapshots
     * @param RenameServiceAdapter          $renameServiceAdapter the rename service adapter
     * @param RetypeServiceAdapter          $retypeServiceAdapter the retype service adapter
     */
    private function __construct(
        private RefactorTransactionContext $context,
        private readonly VirtualFileSnapshotCollection $snapshots,
        private readonly RenameServiceAdapter $renameServiceAdapter,
        private readonly RetypeServiceAdapter $retypeServiceAdapter,
    ) {
    }

    /**
     * Begins a global refactor transaction from one build.
     *
     * @param MemberDependencyGraphBuild $build                the initial build
     * @param RenameServiceAdapter       $renameServiceAdapter the rename service adapter
     * @param RetypeServiceAdapter       $retypeServiceAdapter the retype service adapter
     * @param VirtualFileSnapshotter     $snapshotter          the global virtual file snapshotter
     */
    public static function begin(
        MemberDependencyGraphBuild $build,
        RenameServiceAdapter $renameServiceAdapter,
        RetypeServiceAdapter $retypeServiceAdapter,
        VirtualFileSnapshotter $snapshotter,
    ): self {
        return new self(
            context: RefactorTransactionContext::fromBuild($build),
            snapshots: $snapshotter->snapshot($build->virtualFiles),
            renameServiceAdapter: $renameServiceAdapter,
            retypeServiceAdapter: $retypeServiceAdapter,
        );
    }

    /**
     * Delegates one class FQCN rename to php-rename.
     *
     * @param string               $className      the current class-like FQCN
     * @param string               $newClassName   the replacement class-like FQCN
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameClassFqcn(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClassFqcn',
            arguments: [
                'className' => $className,
                'newClassName' => $newClassName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClassFqcn(
                context: $context,
                className: $className,
                newClassName: $newClassName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one short class rename to php-rename.
     *
     * @param string               $className      the current class-like FQCN
     * @param string               $newClassName   the replacement short class-like name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameClass(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClass',
            arguments: [
                'className' => $className,
                'newClassName' => $newClassName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClass(
                context: $context,
                className: $className,
                newClassName: $newClassName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one method rename to php-rename.
     *
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameMethod(
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'newMethodName' => $newMethodName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                newMethodName: $newMethodName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one property rename to php-rename.
     *
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameProperty(
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameProperty',
            arguments: [
                'className' => $className,
                'propertyName' => $propertyName,
                'newPropertyName' => $newPropertyName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameProperty(
                context: $context,
                className: $className,
                propertyName: $propertyName,
                newPropertyName: $newPropertyName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one class-constant rename to php-rename.
     *
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameClassConstant(
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClassConstant',
            arguments: [
                'className' => $className,
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClassConstant(
                context: $context,
                className: $className,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one enum-case rename to php-rename.
     *
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameEnumCase(
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameEnumCase',
            arguments: [
                'enumName' => $enumName,
                'caseName' => $caseName,
                'newCaseName' => $newCaseName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameEnumCase(
                context: $context,
                enumName: $enumName,
                caseName: $caseName,
                newCaseName: $newCaseName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one short function rename to php-rename.
     *
     * @param string               $functionName    the current function FQCN
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameFunction(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameFunction',
            arguments: [
                'functionName' => $functionName,
                'newFunctionName' => $newFunctionName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameFunction(
                context: $context,
                functionName: $functionName,
                newFunctionName: $newFunctionName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one function FQCN rename to php-rename.
     *
     * @param string               $functionName    the current function FQCN
     * @param string               $newFunctionName the replacement function FQCN
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameFunctionFqcn(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameFunctionFqcn',
            arguments: [
                'functionName' => $functionName,
                'newFunctionName' => $newFunctionName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameFunctionFqcn(
                context: $context,
                functionName: $functionName,
                newFunctionName: $newFunctionName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one short namespace-level constant rename to php-rename.
     *
     * @param string               $constantName    the current constant FQCN
     * @param string               $newConstantName the replacement short constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameConstant(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameConstant',
            arguments: [
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameConstant(
                context: $context,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one namespace-level constant FQCN rename to php-rename.
     *
     * @param string               $constantName    the current constant FQCN
     * @param string               $newConstantName the replacement constant FQCN
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     */
    public function renameConstantFqcn(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameConstantFqcn',
            arguments: [
                'constantName' => $constantName,
                'newConstantName' => $newConstantName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameConstantFqcn(
                context: $context,
                constantName: $constantName,
                newConstantName: $newConstantName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one method parameter rename to php-rename.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameMethodParameter(
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameMethodParameter',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameMethodParameter(
                context: $context,
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
     * Delegates one function parameter rename to php-rename.
     *
     * @param string               $functionName     the function FQCN
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameFunctionParameter(
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameFunctionParameter',
            arguments: [
                'functionName' => $functionName,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameFunctionParameter(
                context: $context,
                functionName: $functionName,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one method parameter type change to php-retype.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     */
    public function changeMethodParameterType(
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeMethodParameterType',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeMethodParameterType(
                context: $context,
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
     * Delegates one function parameter type change to php-retype.
     *
     * @param string                                                       $functionName   the function FQCN
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     */
    public function changeFunctionParameterType(
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeFunctionParameterType',
            arguments: [
                'functionName' => $functionName,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeFunctionParameterType(
                context: $context,
                functionName: $functionName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
        );
    }

    /**
     * Delegates one method return type change to php-retype.
     *
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     */
    public function changeMethodReturnType(
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeMethodReturnType',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeMethodReturnType(
                context: $context,
                className: $className,
                methodName: $methodName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one function return type change to php-retype.
     *
     * @param string                                                       $functionName the function FQCN
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeFunctionReturnType(
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeFunctionReturnType',
            arguments: [
                'functionName' => $functionName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeFunctionReturnType(
                context: $context,
                functionName: $functionName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Commits the global transaction in memory.
     */
    public function commit(): RefactorTransactionResult
    {
        if (RefactorTransactionStatus::ACTIVE !== $this->context->status) {
            return $this->result();
        }

        if (true === $this->context->diagnostics->hasErrors()) {
            $this->rollback();

            return $this->result();
        }

        $this->context = $this->context->withStatus(RefactorTransactionStatus::COMMITTED);

        return $this->result();
    }

    /**
     * Commits the transaction and saves every updated physical source file.
     */
    public function commitAndSave(): RefactorTransactionResult
    {
        $result = $this->commit();

        if (true === $result->isSuccessful()) {
            $result->finalBuild->sourceRegistry()->save();
        }

        return $result;
    }

    /**
     * Commits the transaction and saves one updated physical source file.
     *
     * @param string $sourceFilePath the source file path to save
     */
    public function commitAndSaveSourceFile(string $sourceFilePath): RefactorTransactionResult
    {
        $result = $this->commit();

        if (true === $result->isSuccessful()) {
            $result->finalBuild->sourceRegistry()->saveSourceFile($sourceFilePath);
        }

        return $result;
    }

    /**
     * Restores the global source snapshot.
     */
    public function rollback(): RefactorTransactionResult
    {
        $this->snapshots->restore($this->context->currentBuild->virtualFiles);
        $restoredBuild = MemberDependencyGraphFactory::fromVirtualFiles($this->context->currentBuild->virtualFiles);
        $this->context = $this->context
            ->withCurrentBuild($restoredBuild)
            ->withStatus(RefactorTransactionStatus::ROLLED_BACK);

        return $this->result();
    }

    /**
     * Returns the current transaction result.
     */
    public function result(): RefactorTransactionResult
    {
        return new RefactorTransactionResult(
            status: $this->context->status,
            finalBuild: $this->context->currentBuild,
            diagnostics: $this->context->diagnostics,
            journal: $this->context->journal,
            touchedFiles: $this->context->touchedFiles,
        );
    }

    /**
     * Executes one guarded transaction step.
     *
     * @param string                                                           $service   the service adapter name
     * @param string                                                           $operation the operation name
     * @param array<string, scalar|null>                                       $arguments the operation arguments
     * @param callable(RefactorTransactionContext): RefactorTransactionContext $callback  the step callback
     */
    private function executeStep(
        string $service,
        string $operation,
        array $arguments,
        callable $callback,
    ): self {
        if (RefactorTransactionStatus::ACTIVE !== $this->context->status) {
            return $this;
        }

        try {
            $this->context = $callback($this->context);
        } catch (\Throwable $exception) {
            $diagnostics = RefactorDiagnosticCollection::empty()->add(new RefactorDiagnostic(
                severity: RefactorDiagnosticSeverity::ERROR,
                message: $exception->getMessage(),
                service: $service,
            ));
            $entry = new RefactorActionJournalEntry(
                service: $service,
                operation: $operation,
                arguments: $arguments,
                applied: false,
                diagnostics: $diagnostics,
                touchedFiles: new VirtualPhpSourceFileCollection(),
                exception: $exception::class.': '.$exception->getMessage(),
            );
            $this->context = $this->context
                ->withActionResult($diagnostics, new VirtualPhpSourceFileCollection(), $entry)
                ->withStatus(RefactorTransactionStatus::FAILED);
            $this->rollback();

            return $this;
        }

        if (true === $this->context->diagnostics->hasErrors()) {
            $this->rollback();
        }

        return $this;
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
}
