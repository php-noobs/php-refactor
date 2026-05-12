# PhpRefactor Documentation

Navigation: [Next: Overview](01-overview.md)

This documentation describes the `PhpRefactor` component, its orchestration role, and its boundaries with specialized refactoring services.

`PhpRefactor` is the high-level orchestration package for composed PHP refactoring workflows. It currently orchestrates `php-rename` operations in one global transaction without duplicating semantic resolution or source-writing responsibilities.

## Pages

1. [Overview](01-overview.md)
2. [Public Usage](02-public-usage.md)
3. [Transaction Model](03-transaction-model.md)
4. [Service Adapters](04-service-adapters.md)
5. [Snapshots And Rollback](05-snapshots-and-rollback.md)

## External Dependencies

`PhpRefactor` uses these packages:

- `php-noobs/member-graph` for semantic facts, source-node lookup, projected builds, and in-memory rebuilds;
- `php-noobs/php-source-registry` for virtual source files and physical writing;
- `php-noobs/php-rename` for safe symbol rename operations.

## Current Layout

The project provides a rename-backed orchestration API.

The layout is:

- `Domain/` contains transaction intents, workflow results, diagnostics, and orchestration policies.
- `Application/` contains the public facade and transaction API.
- `Application/Adapter/` adapts specialized services into composed workflows.

The rename adapter consumes the transaction-neutral `php-rename` step API. `PhpRefactor` owns the global transaction lifecycle and never nests `PhpRenameTransaction`.

Navigation: [Next: Overview](01-overview.md)
