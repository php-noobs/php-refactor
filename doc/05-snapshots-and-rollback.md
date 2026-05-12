# Snapshots And Rollback

Navigation: [Documentation](README.md) | [Previous: Service Adapters](04-service-adapters.md)

`php-refactor` owns global source snapshots.

The first implementation snapshots every loaded `VirtualPhpSourceFile` when `beginTransaction()` is called.

This is intentionally broader than the eventually optimized model. It keeps rollback service-neutral: the same snapshot mechanism can restore source mutations made by `php-rename`, `php-retype`, `php-clone`, or future services.

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

## Future Optimization

Later versions may snapshot only files that a service plans to mutate.

That optimization requires reliable pre-apply touched-file information from every participating service. Until then, begin-transaction snapshots are safer and keep the global rollback contract simple.

Navigation: [Documentation](README.md) | [Previous: Service Adapters](04-service-adapters.md)
