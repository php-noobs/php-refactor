<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Transaction;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use BabelForge\PhpRefactor\Domain\Journal\RefactorActionJournal;
use BabelForge\PhpRefactor\Domain\Journal\RefactorActionJournalEntry;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries global transaction state across service adapters.
 */
final readonly class RefactorTransactionContext
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild     $baseBuild       the initial build
     * @param MemberDependencyGraphBuild     $currentBuild    the current semantic build
     * @param array<string, object>          $serviceContexts the service-specific contexts
     * @param VirtualPhpSourceFileCollection $touchedFiles    the virtual files touched by all applied actions
     * @param RefactorDiagnosticCollection   $diagnostics     the aggregated diagnostics
     * @param RefactorActionJournal          $journal         the action journal
     * @param RefactorTransactionStatus      $status          the transaction status
     */
    public function __construct(
        public MemberDependencyGraphBuild $baseBuild,
        public MemberDependencyGraphBuild $currentBuild,
        public array $serviceContexts,
        public VirtualPhpSourceFileCollection $touchedFiles,
        public RefactorDiagnosticCollection $diagnostics,
        public RefactorActionJournal $journal,
        public RefactorTransactionStatus $status = RefactorTransactionStatus::ACTIVE,
    ) {
    }

    /**
     * Creates an initial context from one build.
     *
     * @param MemberDependencyGraphBuild $build the initial build
     */
    public static function fromBuild(MemberDependencyGraphBuild $build): self
    {
        return new self(
            baseBuild: $build,
            currentBuild: $build,
            serviceContexts: [],
            touchedFiles: new VirtualPhpSourceFileCollection(),
            diagnostics: RefactorDiagnosticCollection::empty(),
            journal: RefactorActionJournal::empty(),
        );
    }

    /**
     * Returns a context with an updated current build.
     *
     * @param MemberDependencyGraphBuild $currentBuild the new current build
     */
    public function withCurrentBuild(MemberDependencyGraphBuild $currentBuild): self
    {
        return new self(
            baseBuild: $this->baseBuild,
            currentBuild: $currentBuild,
            serviceContexts: $this->serviceContexts,
            touchedFiles: $this->touchedFiles,
            diagnostics: $this->diagnostics,
            journal: $this->journal,
            status: $this->status,
        );
    }

    /**
     * Returns a context with one service context stored by key.
     *
     * @param string $service the service key
     * @param object $context the service-specific context
     */
    public function withServiceContext(string $service, object $context): self
    {
        return new self(
            baseBuild: $this->baseBuild,
            currentBuild: $this->currentBuild,
            serviceContexts: [
                ...$this->serviceContexts,
                $service => $context,
            ],
            touchedFiles: $this->touchedFiles,
            diagnostics: $this->diagnostics,
            journal: $this->journal,
            status: $this->status,
        );
    }

    /**
     * Returns a stored service context.
     *
     * @param string $service the service key
     */
    public function serviceContext(string $service): ?object
    {
        return $this->serviceContexts[$service] ?? null;
    }

    /**
     * Returns a context with aggregated action effects.
     *
     * @param RefactorDiagnosticCollection   $diagnostics  the diagnostics to append
     * @param VirtualPhpSourceFileCollection $touchedFiles the touched files to append
     * @param RefactorActionJournalEntry     $entry        the journal entry to append
     */
    public function withActionResult(
        RefactorDiagnosticCollection $diagnostics,
        VirtualPhpSourceFileCollection $touchedFiles,
        RefactorActionJournalEntry $entry,
    ): self {
        $mergedTouchedFiles = $this->copyTouchedFiles();
        foreach ($touchedFiles as $file) {
            if (true === $mergedTouchedFiles->has($file->virtualFilePath)) {
                continue;
            }

            $mergedTouchedFiles->add($file);
        }

        $mergedDiagnostics = RefactorDiagnosticCollection::empty()->merge($this->diagnostics)->merge($diagnostics);
        $journal = $this->copyJournal()->add($entry);

        return new self(
            baseBuild: $this->baseBuild,
            currentBuild: $this->currentBuild,
            serviceContexts: $this->serviceContexts,
            touchedFiles: $mergedTouchedFiles,
            diagnostics: $mergedDiagnostics,
            journal: $journal,
            status: $this->status,
        );
    }

    /**
     * Returns a context with another status.
     *
     * @param RefactorTransactionStatus $status the new transaction status
     */
    public function withStatus(RefactorTransactionStatus $status): self
    {
        return new self(
            baseBuild: $this->baseBuild,
            currentBuild: $this->currentBuild,
            serviceContexts: $this->serviceContexts,
            touchedFiles: $this->touchedFiles,
            diagnostics: $this->diagnostics,
            journal: $this->journal,
            status: $status,
        );
    }

    /**
     * Copies touched file references into a new collection.
     */
    private function copyTouchedFiles(): VirtualPhpSourceFileCollection
    {
        $copy = new VirtualPhpSourceFileCollection();

        foreach ($this->touchedFiles as $file) {
            $copy->add($file);
        }

        return $copy;
    }

    /**
     * Copies journal entries into a new journal.
     */
    private function copyJournal(): RefactorActionJournal
    {
        $copy = RefactorActionJournal::empty();

        foreach ($this->journal as $entry) {
            $copy->add($entry);
        }

        return $copy;
    }
}
