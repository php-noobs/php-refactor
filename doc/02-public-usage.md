# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md)

The public API exposes composed workflows while preserving the plan-before-apply model used by specialized services.

## Facade

The facade supports rename-backed and retype-backed transactions:

```php
use PhpNoobs\PhpRefactor\Application\PhpRefactor;
use PhpParser\Node\Name;

$refactor = PhpRefactor::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);

$transaction = $refactor->beginTransaction();

$result = $transaction
    ->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender')
    ->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver')
    ->changeMethodReturnType('App\\Infrastructure\\Sender', 'deliver', new Name('DeliveryResult'), 'DeliveryResult')
    ->commitAndSave();
```

## Build Ownership

`PhpRefactor` supports two construction paths:

- `fromDirectory()` when it owns the initial `member-graph` build;
- `fromBuild()` when another caller already owns a `MemberDependencyGraphBuild`.

The same build and source registry are shared across rename and retype steps.

## Type Changes

`php-refactor` exposes the type-change operations that `php-retype` supports through its step API:

- `changeMethodParameterType()`;
- `changeFunctionParameterType()`;
- `changeMethodReturnType()`;
- `changeFunctionReturnType()`;
- `changePropertyType()`;
- `changeClassConstantType()`;
- `changeEnumBackingType()`.

Each method accepts a PHP-Parser type node for the native type and an optional PHPDoc type string. A `null` native type removes the native declaration while preserving the PHPDoc change when a doc type is provided.

`changePropertyType()` accepts one property name or a list of property names. Grouped property declaration splitting, promoted property mutation, and direct `@var` updates are handled by `php-retype`.

`changeClassConstantType()` changes class constant native types and direct `@var` tags. Grouped class constant declaration splitting is handled by `php-retype`.

`changeEnumBackingType()` changes enum backing types and accepts `int` or `string` identifiers.

## Transaction Rules

Transactions:

- plan before mutating;
- stop on blocking diagnostics;
- keep aggregate diagnostics;
- refresh semantic state after each successful operation;
- rollback earlier in-memory mutations when possible;
- write through the final build source registry, not through direct filesystem writes.

The transaction snapshots all loaded virtual files at `beginTransaction()`. It delegates rename steps to `php-rename`, delegates type-change steps to `php-retype`, and rolls back the global snapshot when a blocking diagnostic or unexpected exception appears.

## Graph Freshness

Each step uses the current build returned by the previous service step. This allows mixed workflows such as renaming a method and then changing the return type of the renamed method in the same global transaction.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Transaction Model](03-transaction-model.md)
