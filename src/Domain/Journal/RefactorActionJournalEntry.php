<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Journal;

use BabelForge\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes one attempted action in a global refactor transaction.
 */
final readonly class RefactorActionJournalEntry
{
    /**
     * Constructor.
     *
     * @param string                         $service      the service adapter name
     * @param string                         $operation    the requested operation name
     * @param array<string, scalar|null>     $arguments    the scalar operation arguments
     * @param bool                           $applied      whether the action was applied
     * @param RefactorDiagnosticCollection   $diagnostics  the action diagnostics
     * @param VirtualPhpSourceFileCollection $touchedFiles the virtual files touched by the action
     * @param string|null                    $exception    the exception class and message when the action failed unexpectedly
     */
    public function __construct(
        public string $service,
        public string $operation,
        public array $arguments,
        public bool $applied,
        public RefactorDiagnosticCollection $diagnostics,
        public VirtualPhpSourceFileCollection $touchedFiles,
        public ?string $exception = null,
    ) {
    }
}
