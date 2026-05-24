<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Transaction;

/**
 * Enumerates global refactor transaction statuses.
 */
enum RefactorTransactionStatus
{
    case ACTIVE;
    case COMMITTED;
    case ROLLED_BACK;
    case FAILED;
}
