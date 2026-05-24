<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Diagnostic;

/**
 * Enumerates refactor diagnostic severities.
 */
enum RefactorDiagnosticSeverity
{
    case INFO;
    case WARNING;
    case ERROR;
}
