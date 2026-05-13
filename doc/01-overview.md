# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRefactor` is a user-facing orchestrator, not a replacement for lower-level refactoring services.

Its responsibility is to compose safe operations in a coherent order:

- rename symbols through `php-rename`;
- change native and PHPDoc types through `php-retype`;
- delegate property and class constant declaration splitting to `php-retype`;
- delegate promoted property, enum backing type, and nested callable handling to `php-retype`;
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

`PhpRefactor` does not perform textual source search, duplicate semantic resolution, or mutate arbitrary AST nodes outside a specialized operation contract.

## Current Status

The package provides the high-level transaction layer above `php-rename` and `php-retype`.

The implementation:

- builds or receives one `member-graph` build;
- creates a global `PhpRefactorTransaction`;
- snapshots all loaded virtual files at `beginTransaction()`;
- delegates rename operations to `php-rename` step APIs;
- delegates type-change operations to `php-retype` step APIs;
- exposes closure and arrow-function type changes inside method, function, and file containers;
- aggregates diagnostics and action journal entries;
- rolls back globally on blocking diagnostics or unexpected exceptions;
- saves only through the final build source registry.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
