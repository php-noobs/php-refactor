# Snapshots And Rollback

Navigation: [Documentation](README.md) | [Previous: Service Adapters](04-service-adapters.md)

`php-refactor` owns global source snapshots.

The implementation snapshots every loaded `VirtualPhpSourceFile` when `beginTransaction()` is called.

This keeps rollback independent from specialized services: the global transaction restores source state without calling `PhpRenameTransaction` or `PhpRetypeTransaction`.

## Snapshot Contents

Each virtual file snapshot stores:

- virtual file path;
- printed code;
- transformed AST nodes;
- original non-transformed AST nodes;
- update flag.

Nodes are deep-copied through serialization so later AST mutation does not affect the stored snapshot.

## Rollback Flow

Rollback:

1. restores every matching virtual file from the begin-transaction snapshot;
2. rebuilds a fresh in-memory `MemberDependencyGraphBuild` from restored virtual files;
3. marks the transaction as `ROLLED_BACK`.

Rollback is triggered when:

- a step produces blocking diagnostics;
- an unexpected exception is thrown during step execution;
- `commit()` sees accumulated error diagnostics.

The restored build is the build returned in the final transaction result, so callers inspect the same virtual file state that remains after rollback.

Navigation: [Documentation](README.md) | [Previous: Service Adapters](04-service-adapters.md)
