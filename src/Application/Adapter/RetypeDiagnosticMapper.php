<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Adapter;

use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnostic;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;

/**
 * Maps php-retype diagnostics to global refactor diagnostics.
 */
final readonly class RetypeDiagnosticMapper
{
    /**
     * Maps a retype diagnostic collection.
     *
     * @param RetypeDiagnosticCollection $diagnostics the retype diagnostics
     */
    public function map(RetypeDiagnosticCollection $diagnostics): RefactorDiagnosticCollection
    {
        $mapped = RefactorDiagnosticCollection::empty();

        foreach ($diagnostics as $diagnostic) {
            $mapped->add(new RefactorDiagnostic(
                severity: $this->mapSeverity($diagnostic->severity),
                message: $diagnostic->message,
                service: RetypeServiceAdapter::SERVICE,
            ));
        }

        return $mapped;
    }

    /**
     * Maps one retype severity.
     *
     * @param RetypeDiagnosticSeverity $severity the retype severity
     */
    private function mapSeverity(RetypeDiagnosticSeverity $severity): RefactorDiagnosticSeverity
    {
        return match ($severity) {
            RetypeDiagnosticSeverity::INFO => RefactorDiagnosticSeverity::INFO,
            RetypeDiagnosticSeverity::WARNING => RefactorDiagnosticSeverity::WARNING,
            RetypeDiagnosticSeverity::ERROR => RefactorDiagnosticSeverity::ERROR,
        };
    }
}
