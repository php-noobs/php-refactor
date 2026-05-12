<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Adapter;

use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnostic;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;

/**
 * Maps php-rename diagnostics to global refactor diagnostics.
 */
final readonly class RenameDiagnosticMapper
{
    /**
     * Maps a rename diagnostic collection.
     *
     * @param RenameDiagnosticCollection $diagnostics the rename diagnostics
     */
    public function map(RenameDiagnosticCollection $diagnostics): RefactorDiagnosticCollection
    {
        $mapped = RefactorDiagnosticCollection::empty();

        foreach ($diagnostics as $diagnostic) {
            $mapped->add(new RefactorDiagnostic(
                severity: $this->mapSeverity($diagnostic->severity),
                message: $diagnostic->message,
                service: RenameServiceAdapter::SERVICE,
            ));
        }

        return $mapped;
    }

    /**
     * Maps one rename severity.
     *
     * @param RenameDiagnosticSeverity $severity the rename severity
     */
    private function mapSeverity(RenameDiagnosticSeverity $severity): RefactorDiagnosticSeverity
    {
        return match ($severity) {
            RenameDiagnosticSeverity::INFO => RefactorDiagnosticSeverity::INFO,
            RenameDiagnosticSeverity::WARNING => RefactorDiagnosticSeverity::WARNING,
            RenameDiagnosticSeverity::ERROR => RefactorDiagnosticSeverity::ERROR,
        };
    }
}
