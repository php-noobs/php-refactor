<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Adapter;

use PhpNoobs\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionContext;
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;

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
        $renameContext = $this->renameContext($context);
        $step = $this->renamer->executeStepClassFqcnRename(
            context: $renameContext,
            className: $className,
            newClassName: $newClassName,
            conflictPolicy: $conflictPolicy,
        );
        $diagnostics = $this->diagnosticMapper->map($step->diagnostics);
        $entry = new RefactorActionJournalEntry(
            service: self::SERVICE,
            operation: 'renameClassFqcn',
            arguments: [
                'className' => $className,
                'newClassName' => $newClassName,
            ],
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
        $renameContext = $this->renameContext($context);
        $step = $this->renamer->executeStepMethodRename(
            context: $renameContext,
            className: $className,
            methodName: $methodName,
            newMethodName: $newMethodName,
            conflictPolicy: $conflictPolicy,
        );
        $diagnostics = $this->diagnosticMapper->map($step->diagnostics);
        $entry = new RefactorActionJournalEntry(
            service: self::SERVICE,
            operation: 'renameMethod',
            arguments: [
                'className' => $className,
                'methodName' => $methodName,
                'newMethodName' => $newMethodName,
            ],
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
