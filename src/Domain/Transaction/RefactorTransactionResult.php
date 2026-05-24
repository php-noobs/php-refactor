<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Transaction;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use BabelForge\PhpRefactor\Domain\Journal\RefactorActionJournal;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the final state of a global refactor transaction.
 */
final readonly class RefactorTransactionResult
{
    /**
     * Constructor.
     *
     * @param RefactorTransactionStatus      $status       the transaction status
     * @param MemberDependencyGraphBuild     $finalBuild   the final semantic build
     * @param RefactorDiagnosticCollection   $diagnostics  the aggregated diagnostics
     * @param RefactorActionJournal          $journal      the action journal
     * @param VirtualPhpSourceFileCollection $touchedFiles the touched virtual files
     */
    public function __construct(
        public RefactorTransactionStatus $status,
        public MemberDependencyGraphBuild $finalBuild,
        public RefactorDiagnosticCollection $diagnostics,
        public RefactorActionJournal $journal,
        public VirtualPhpSourceFileCollection $touchedFiles,
    ) {
    }

    /**
     * Indicates whether the transaction committed successfully.
     */
    public function isSuccessful(): bool
    {
        return RefactorTransactionStatus::COMMITTED === $this->status
            && false === $this->diagnostics->hasErrors();
    }
}
