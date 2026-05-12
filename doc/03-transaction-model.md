# Transaction Model

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Service Adapters](04-service-adapters.md)

`PhpRefactorTransaction` is the global transaction coordinator.

It owns the lifecycle that specialized services deliberately do not own:

- begin;
- global snapshot capture;
- step ordering;
- diagnostics aggregation;
- action journaling;
- rollback;
- commit;
- final persistence.

## Context

`RefactorTransactionContext` carries the transaction state shared across adapters:

- the initial `MemberDependencyGraphBuild`;
- the current `MemberDependencyGraphBuild`;
- service-specific contexts;
- touched virtual files;
- aggregate diagnostics;
- action journal;
- transaction status.

The context is updated immutably by adapters. This keeps each step explicit and makes later journal/debug tooling easier to add.

## Statuses

The first statuses are:

- `ACTIVE`;
- `COMMITTED`;
- `ROLLED_BACK`;
- `FAILED`.

A transaction starts as `ACTIVE`.

When a step returns blocking diagnostics, the transaction restores the global snapshot and becomes `ROLLED_BACK`.

Unexpected exceptions are converted into error diagnostics, journaled, and followed by global rollback.

## Commit

`commit()` finalizes the in-memory transaction only.

`commitAndSave()` commits and then calls `sourceRegistry()->save()` on the final build.

`commitAndSaveSourceFile()` commits and then calls `sourceRegistry()->saveSourceFile()` on the final build.

Specialized services do not write physical files during global orchestration.

## Service Transactions

`PhpRefactorTransaction` owns the cross-service transaction. It calls transaction-neutral service step APIs and never opens service-local transactions.

`PhpRenameTransaction` and `PhpRetypeTransaction` remain standalone entry points for their own packages. They are not nested inside a `PhpRefactorTransaction`.

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Service Adapters](04-service-adapters.md)
