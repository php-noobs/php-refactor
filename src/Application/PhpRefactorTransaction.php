<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRefactor\Application\Adapter\RenameServiceAdapter;
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
     */
    private function __construct(
        private RefactorTransactionContext $context,
        private readonly VirtualFileSnapshotCollection $snapshots,
        private readonly RenameServiceAdapter $renameServiceAdapter,
    ) {
    }

    /**
     * Begins a global refactor transaction from one build.
     *
     * @param MemberDependencyGraphBuild $build                the initial build
     * @param RenameServiceAdapter       $renameServiceAdapter the rename service adapter
     * @param VirtualFileSnapshotter     $snapshotter          the global virtual file snapshotter
     */
    public static function begin(
        MemberDependencyGraphBuild $build,
        RenameServiceAdapter $renameServiceAdapter,
        VirtualFileSnapshotter $snapshotter,
    ): self {
        return new self(
            context: RefactorTransactionContext::fromBuild($build),
            snapshots: $snapshotter->snapshot($build->virtualFiles),
            renameServiceAdapter: $renameServiceAdapter,
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
     * @param string                                                           $operation the operation name
     * @param array<string, scalar|null>                                       $arguments the operation arguments
     * @param callable(RefactorTransactionContext): RefactorTransactionContext $callback  the step callback
     */
    private function executeStep(
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
                service: RenameServiceAdapter::SERVICE,
            ));
            $entry = new RefactorActionJournalEntry(
                service: RenameServiceAdapter::SERVICE,
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
}
