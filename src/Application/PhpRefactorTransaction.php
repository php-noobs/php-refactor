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
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;
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
     * Delegates one preplanned rename step to php-rename.
     *
     * @param RenamePlan $plan the rename plan to execute
     */
    public function executeRenamePlan(RenamePlan $plan): self
    {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'executeRenamePlan',
            arguments: [
                'plan' => $plan::class,
                'request' => $plan->request::class,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->executeStep(
                context: $context,
                plan: $plan,
            ),
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
     * Delegates one nested callable parameter rename to php-rename.
     *
     * @param NestedCallableRenameRequest $request the nested callable parameter rename request
     */
    public function renameNestedCallableParameter(NestedCallableRenameRequest $request): self
    {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameNestedCallableParameter',
            arguments: [
                'request' => $request::class,
                'callableIndex' => $request->callableIndex,
                'parameterName' => $request->parameterName,
                'newName' => $request->newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameNestedCallableParameter(
                context: $context,
                request: $request,
            ),
        );
    }

    /**
     * Delegates one closure parameter rename inside a method to php-rename.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $closureIndex     the zero-based closure index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameClosureParameterInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureParameterInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureParameterInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function parameter rename inside a method to php-rename.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $arrowIndex       the zero-based arrow-function index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameArrowFunctionParameterInMethod(
        string $className,
        string $methodName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionParameterInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'arrowIndex' => $arrowIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionParameterInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                arrowIndex: $arrowIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one closure parameter rename inside a function to php-rename.
     *
     * @param string               $functionName     the function FQCN
     * @param int                  $closureIndex     the zero-based closure index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameClosureParameterInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureParameterInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureParameterInFunction(
                context: $context,
                functionName: $functionName,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function parameter rename inside a function to php-rename.
     *
     * @param string               $functionName     the function FQCN
     * @param int                  $arrowIndex       the zero-based arrow-function index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameArrowFunctionParameterInFunction(
        string $functionName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionParameterInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowIndex' => $arrowIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionParameterInFunction(
                context: $context,
                functionName: $functionName,
                arrowIndex: $arrowIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one closure parameter rename inside a file to php-rename.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $closureIndex     the zero-based closure index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameClosureParameterInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureParameterInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureParameterInFile(
                context: $context,
                filePath: $filePath,
                closureIndex: $closureIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function parameter rename inside a file to php-rename.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $arrowIndex       the zero-based arrow-function index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based parameter index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     */
    public function renameArrowFunctionParameterInFile(
        string $filePath,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionParameterInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowIndex' => $arrowIndex,
                'parameterName' => $parameterName,
                'newParameterName' => $newParameterName,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionParameterInFile(
                context: $context,
                filePath: $filePath,
                arrowIndex: $arrowIndex,
                parameterName: $parameterName,
                newParameterName: $newParameterName,
                parameterIndex: $parameterIndex,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one nested callable local variable rename to php-rename.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     */
    public function renameNestedCallableLocalVariable(NestedCallableLocalVariableRenameRequest $request): self
    {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameNestedCallableLocalVariable',
            arguments: [
                'request' => $request::class,
                'callableIndex' => $request->callableIndex,
                'variableName' => $request->variableName,
                'newName' => $request->newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameNestedCallableLocalVariable(
                context: $context,
                request: $request,
            ),
        );
    }

    /**
     * Delegates one closure local variable rename inside a method to php-rename.
     *
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $closureIndex   the zero-based closure index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameClosureLocalVariableInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureLocalVariableInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'closureIndex' => $closureIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureLocalVariableInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                closureIndex: $closureIndex,
                variableName: $variableName,
                newName: $newName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function local variable rename inside a method to php-rename.
     *
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $arrowIndex     the zero-based arrow-function index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameArrowFunctionLocalVariableInMethod(
        string $className,
        string $methodName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionLocalVariableInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'arrowIndex' => $arrowIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionLocalVariableInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                arrowIndex: $arrowIndex,
                variableName: $variableName,
                newName: $newName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one closure local variable rename inside a function to php-rename.
     *
     * @param string               $functionName   the function FQCN
     * @param int                  $closureIndex   the zero-based closure index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameClosureLocalVariableInFunction(
        string $functionName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureLocalVariableInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureLocalVariableInFunction(
                context: $context,
                functionName: $functionName,
                closureIndex: $closureIndex,
                variableName: $variableName,
                newName: $newName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function local variable rename inside a function to php-rename.
     *
     * @param string               $functionName   the function FQCN
     * @param int                  $arrowIndex     the zero-based arrow-function index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameArrowFunctionLocalVariableInFunction(
        string $functionName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionLocalVariableInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowIndex' => $arrowIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionLocalVariableInFunction(
                context: $context,
                functionName: $functionName,
                arrowIndex: $arrowIndex,
                variableName: $variableName,
                newName: $newName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one closure local variable rename inside a file to php-rename.
     *
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $closureIndex   the zero-based closure index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameClosureLocalVariableInFile(
        string $filePath,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameClosureLocalVariableInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameClosureLocalVariableInFile(
                context: $context,
                filePath: $filePath,
                closureIndex: $closureIndex,
                variableName: $variableName,
                newName: $newName,
                conflictPolicy: $conflictPolicy,
            ),
        );
    }

    /**
     * Delegates one arrow-function local variable rename inside a file to php-rename.
     *
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $arrowIndex     the zero-based arrow-function index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     */
    public function renameArrowFunctionLocalVariableInFile(
        string $filePath,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): self {
        return $this->executeStep(
            service: RenameServiceAdapter::SERVICE,
            operation: 'renameArrowFunctionLocalVariableInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowIndex' => $arrowIndex,
                'variableName' => $variableName,
                'newName' => $newName,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->renameServiceAdapter->renameArrowFunctionLocalVariableInFile(
                context: $context,
                filePath: $filePath,
                arrowIndex: $arrowIndex,
                variableName: $variableName,
                newName: $newName,
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
     * Delegates one property type change to php-retype.
     *
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     */
    public function changePropertyType(
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changePropertyType',
            arguments: [
                'className' => $className,
                'propertyNames' => $this->propertyNamesLabel($propertyNames),
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changePropertyType(
                context: $context,
                className: $className,
                propertyNames: $propertyNames,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one class constant type change to php-retype.
     *
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     */
    public function changeClassConstantType(
        string $className,
        string $constantName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClassConstantType',
            arguments: [
                'className' => $className,
                'constantName' => $constantName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClassConstantType(
                context: $context,
                className: $className,
                constantName: $constantName,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one enum backing type change to php-retype.
     *
     * @param string     $enumName the enum FQCN
     * @param Identifier $typeNode the native PHP backing type node to write
     */
    public function changeEnumBackingType(string $enumName, Identifier $typeNode): self
    {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeEnumBackingType',
            arguments: [
                'enumName' => $enumName,
                'typeNode' => $this->typeNodeLabel($typeNode),
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeEnumBackingType(
                context: $context,
                enumName: $enumName,
                typeNode: $typeNode,
            ),
        );
    }

    /**
     * Delegates one closure parameter type change inside a method to php-retype.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
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
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureParameterTypeInMethod(
                context: $context,
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
     * Delegates one closure return type change inside a method to php-retype.
     *
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClosureReturnTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureReturnTypeInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one arrow function parameter type change inside a method to php-retype.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
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
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionParameterTypeInMethod(
                context: $context,
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
     * Delegates one arrow function return type change inside a method to php-retype.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeArrowFunctionReturnTypeInMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionReturnTypeInMethod(
                context: $context,
                className: $className,
                methodName: $methodName,
                arrowFunctionIndex: $arrowFunctionIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one closure parameter type change inside a function to php-retype.
     *
     * @param string                                                       $functionName   the function FQCN
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClosureParameterTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureParameterTypeInFunction(
                context: $context,
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
     * Delegates one closure return type change inside a function to php-retype.
     *
     * @param string                                                       $functionName the function FQCN
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInFunction(
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClosureReturnTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureReturnTypeInFunction(
                context: $context,
                functionName: $functionName,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one arrow function parameter type change inside a function to php-retype.
     *
     * @param string                                                       $functionName       the function FQCN
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeArrowFunctionParameterTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionParameterTypeInFunction(
                context: $context,
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
     * Delegates one arrow function return type change inside a function to php-retype.
     *
     * @param string                                                       $functionName       the function FQCN
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeArrowFunctionReturnTypeInFunction',
            arguments: [
                'functionName' => $functionName,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionReturnTypeInFunction(
                context: $context,
                functionName: $functionName,
                arrowFunctionIndex: $arrowFunctionIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one closure parameter type change inside a file to php-retype.
     *
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     */
    public function changeClosureParameterTypeInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClosureParameterTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureParameterTypeInFile(
                context: $context,
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
     * Delegates one closure return type change inside a file to php-retype.
     *
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     */
    public function changeClosureReturnTypeInFile(
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeClosureReturnTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'closureIndex' => $closureIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeClosureReturnTypeInFile(
                context: $context,
                filePath: $filePath,
                closureIndex: $closureIndex,
                typeNode: $typeNode,
                docType: $docType,
            ),
        );
    }

    /**
     * Delegates one arrow function parameter type change inside a file to php-retype.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     */
    public function changeArrowFunctionParameterTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeArrowFunctionParameterTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'parameterName' => $parameterName,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
                'parameterIndex' => $parameterIndex,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionParameterTypeInFile(
                context: $context,
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
     * Delegates one arrow function return type change inside a file to php-retype.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     */
    public function changeArrowFunctionReturnTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): self {
        return $this->executeStep(
            service: RetypeServiceAdapter::SERVICE,
            operation: 'changeArrowFunctionReturnTypeInFile',
            arguments: [
                'filePath' => $filePath,
                'arrowFunctionIndex' => $arrowFunctionIndex,
                'typeNode' => $this->typeNodeLabel($typeNode),
                'docType' => $docType,
            ],
            callback: fn (RefactorTransactionContext $context): RefactorTransactionContext => $this->retypeServiceAdapter->changeArrowFunctionReturnTypeInFile(
                context: $context,
                filePath: $filePath,
                arrowFunctionIndex: $arrowFunctionIndex,
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
