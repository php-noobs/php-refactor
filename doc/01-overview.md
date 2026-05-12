# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRefactor` should be a user-facing orchestrator, not a replacement for lower-level refactoring services.

Its responsibility is to compose safe operations in a coherent order:

- rename symbols through `php-rename`;
- change type contracts through `php-retype`;
- duplicate or move structures through future specialized services;
- keep graph state fresh between operations;
- save source changes only through `php-source-registry`.

## Package Boundary

`PhpRefactor` owns orchestration:

- multi-step workflow ordering;
- transaction lifecycle;
- aggregate diagnostics;
- all-or-nothing policies;
- dry-run, report-only, save-all, and save-targeted-file policies.

Specialized services own their own operations:

- semantic target discovery;
- operation planning;
- AST mutation for the supported target type;
- operation-specific diagnostics;
- service-level transaction behavior when available.

`PhpRefactor` must not perform textual source search, duplicate semantic resolution, or mutate arbitrary AST nodes outside a specialized operation contract.

## Current Status

The current project provides the first high-level package slice above `php-rename`.

The first implementation:

- builds or receives one `member-graph` build;
- creates a global `PhpRefactorTransaction`;
- snapshots all loaded virtual files at `beginTransaction()`;
- delegates `renameClassFqcn()` and `renameMethod()` to `php-rename` step APIs;
- aggregates diagnostics and action journal entries;
- rolls back globally on blocking diagnostics or unexpected exceptions;
- saves only through the final build source registry.

## Direction

The package should grow from concrete composed workflows.

Broad abstractions should be introduced only when several workflows prove the same orchestration pattern. The first implementation should stay close to existing public service APIs such as `PhpRename::fromBuild()` and `PhpRetype::fromBuild()`.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
