<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Domain\Diagnostic;

/**
 * Enumerates refactor diagnostic severities.
 */
enum RefactorDiagnosticSeverity
{
    case INFO;
    case WARNING;
    case ERROR;
}
