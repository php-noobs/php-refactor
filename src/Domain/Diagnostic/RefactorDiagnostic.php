<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Domain\Diagnostic;

/**
 * Describes one global refactor diagnostic.
 */
final readonly class RefactorDiagnostic
{
    /**
     * Constructor.
     *
     * @param RefactorDiagnosticSeverity $severity the diagnostic severity
     * @param string                     $message  the diagnostic message
     * @param string|null                $service  the optional service that emitted the diagnostic
     */
    public function __construct(
        public RefactorDiagnosticSeverity $severity,
        public string $message,
        public ?string $service = null,
    ) {
    }
}
