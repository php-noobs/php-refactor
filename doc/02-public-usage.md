# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md)

The public API exposes composed workflows while preserving the plan-before-apply model used by specialized services.

## Intended Facade

The first facade supports rename-backed transactions:

```php
use PhpNoobs\PhpRefactor\Application\PhpRefactor;

$refactor = PhpRefactor::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);

$transaction = $refactor->beginTransaction();

$result = $transaction
    ->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender')
    ->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver')
    ->commitAndSave();
```

## Build Ownership

`PhpRefactor` should support two construction paths:

- `fromDirectory()` when it owns the initial `member-graph` build;
- `fromBuild()` when another caller already owns a `MemberDependencyGraphBuild`.

The same build and source registry should be shared across specialized services whenever possible.

## Transaction Rules

Transactions should:

- plan before mutating;
- stop on blocking diagnostics;
- keep aggregate diagnostics;
- refresh semantic state after each successful operation;
- rollback earlier in-memory mutations when possible;
- write through the final build source registry, not through direct filesystem writes.

The first transaction implementation snapshots all loaded virtual files at `beginTransaction()`. It then delegates rename steps to `php-rename` and rolls back the global snapshot when a blocking diagnostic appears.

## Graph Freshness

Identity-only rename operations can often use projected builds from `member-graph`.

Operations that change type information or otherwise affect semantic resolution should use an in-memory rebuild from updated virtual files before planning the next step.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Transaction Model](03-transaction-model.md)
