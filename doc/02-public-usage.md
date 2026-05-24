# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md)

The public API exposes composed workflows while preserving the plan-before-apply model used by specialized services.

## Facade

The facade supports rename-backed and retype-backed transactions:

```php
use BabelForge\PhpRefactor\Application\PhpRefactor;
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

## Nested Callable Type Changes

`php-refactor` exposes closure and arrow-function type changes inside method, function, and file containers:

- `changeClosureParameterTypeInMethod()`;
- `changeClosureReturnTypeInMethod()`;
- `changeArrowFunctionParameterTypeInMethod()`;
- `changeArrowFunctionReturnTypeInMethod()`;
- `changeClosureParameterTypeInFunction()`;
- `changeClosureReturnTypeInFunction()`;
- `changeArrowFunctionParameterTypeInFunction()`;
- `changeArrowFunctionReturnTypeInFunction()`;
- `changeClosureParameterTypeInFile()`;
- `changeClosureReturnTypeInFile()`;
- `changeArrowFunctionParameterTypeInFile()`;
- `changeArrowFunctionReturnTypeInFile()`.

Method containers are identified by class FQCN and method name. Function containers are identified by function FQCN. File containers are identified by file path. The target closure or arrow function is selected by its zero-based DFS index inside the chosen container.

Parameter methods accept the target parameter name and an optional zero-based parameter index. Return methods target the selected closure or arrow function return type. Native type nodes and PHPDoc type strings follow the same rules as top-level method and function type changes.

## Nested Callable Renames

`php-refactor` exposes closure and arrow-function parameter renames inside method, function, and file containers:

- `renameNestedCallableParameter()`;
- `renameClosureParameterInMethod()`;
- `renameArrowFunctionParameterInMethod()`;
- `renameClosureParameterInFunction()`;
- `renameArrowFunctionParameterInFunction()`;
- `renameClosureParameterInFile()`;
- `renameArrowFunctionParameterInFile()`.

It also exposes closure and arrow-function local variable renames inside the same containers:

- `renameNestedCallableLocalVariable()`;
- `renameClosureLocalVariableInMethod()`;
- `renameArrowFunctionLocalVariableInMethod()`;
- `renameClosureLocalVariableInFunction()`;
- `renameArrowFunctionLocalVariableInFunction()`;
- `renameClosureLocalVariableInFile()`;
- `renameArrowFunctionLocalVariableInFile()`.

Method containers are identified by class FQCN and method name. Function containers are identified by function FQCN. File containers are identified by file path. The selected closure or arrow function uses the same zero-based DFS index model as nested callable type changes.

`php-rename` handles scoped mutation, closure captures, arrow-function captures, supported `@param` docblocks for parameter renames, local variable conflict checks, import rewrites for FQCN renames, and graph refresh after each applied step.

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
