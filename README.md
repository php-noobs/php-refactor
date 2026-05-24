# PhpRefactor

`PhpRefactor` is the high-level transaction orchestrator for safe PHP refactoring workflows.

It coordinates `babelforge/php-rename` and `babelforge/php-retype` operations through one global transaction. It owns transaction lifecycle, source snapshots, rollback, diagnostics aggregation, cross-service graph freshness, and final persistence.

## Installation

```bash
composer require babelforge/php-refactor
```

When using the BabelForge packages from GitHub, configure the VCS repositories:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/babelforge/member-graph"
        },
        {
            "type": "vcs",
            "url": "https://github.com/babelforge/php-source-registry"
        },
        {
            "type": "vcs",
            "url": "https://github.com/babelforge/php-rename"
        },
        {
            "type": "vcs",
            "url": "https://github.com/babelforge/php-retype"
        }
    ]
}
```

## Usage

```php
use BabelForge\PhpRefactor\Application\PhpRefactor;
use PhpParser\Node\Name;

$refactor = PhpRefactor::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);

$result = $refactor
    ->beginTransaction()
    ->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender')
    ->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver')
    ->changeMethodReturnType('App\\Infrastructure\\Sender', 'deliver', new Name('DeliveryResult'), 'DeliveryResult')
    ->commitAndSave();

if (false === $result->isSuccessful()) {
    foreach ($result->diagnostics as $diagnostic) {
        echo $diagnostic->message . PHP_EOL;
    }
}
```

`commit()` keeps changes in memory. `commitAndSave()` writes every updated physical source file through `php-source-registry`. `commitAndSaveSourceFile()` writes one known updated source file.

## Supported Rename Operations

- `renameClass()`
- `renameClassFqcn()`
- `renameMethod()`
- `renameProperty()`
- `renameClassConstant()`
- `renameEnumCase()`
- `renameFunction()`
- `renameFunctionFqcn()`
- `renameConstant()`
- `renameConstantFqcn()`
- `renameMethodParameter()`
- `renameFunctionParameter()`
- `renameNestedCallableParameter()`
- `renameClosureParameterInMethod()`
- `renameArrowFunctionParameterInMethod()`
- `renameClosureParameterInFunction()`
- `renameArrowFunctionParameterInFunction()`
- `renameClosureParameterInFile()`
- `renameArrowFunctionParameterInFile()`
- `renameNestedCallableLocalVariable()`
- `renameClosureLocalVariableInMethod()`
- `renameArrowFunctionLocalVariableInMethod()`
- `renameClosureLocalVariableInFunction()`
- `renameArrowFunctionLocalVariableInFunction()`
- `renameClosureLocalVariableInFile()`
- `renameArrowFunctionLocalVariableInFile()`

## Supported Type-Change Operations

- `changeMethodParameterType()`
- `changeFunctionParameterType()`
- `changeMethodReturnType()`
- `changeFunctionReturnType()`
- `changePropertyType()`
- `changeClassConstantType()`
- `changeEnumBackingType()`
- `changeClosureParameterTypeInMethod()`
- `changeClosureReturnTypeInMethod()`
- `changeArrowFunctionParameterTypeInMethod()`
- `changeArrowFunctionReturnTypeInMethod()`
- `changeClosureParameterTypeInFunction()`
- `changeClosureReturnTypeInFunction()`
- `changeArrowFunctionParameterTypeInFunction()`
- `changeArrowFunctionReturnTypeInFunction()`
- `changeClosureParameterTypeInFile()`
- `changeClosureReturnTypeInFile()`
- `changeArrowFunctionParameterTypeInFile()`
- `changeArrowFunctionReturnTypeInFile()`

`PhpRefactor` owns the cross-service transaction. It calls service step APIs and never nests `PhpRenameTransaction` or `PhpRetypeTransaction`.

Property and class constant type changes support grouped declaration splitting through `php-retype`. Property type changes also support promoted properties and direct `@var` updates. Enum backing type changes support `int` and `string`.

Nested callable type changes target closures or arrow functions inside a method, function, or file container. The callable is selected by its zero-based DFS index inside that container. `php-retype` resolves the container, mutates the native type, updates directly attached PHPDoc when available, and refreshes the graph for the next transaction step.

Nested callable renames target closure or arrow-function parameters and local variables inside a method, function, or file container. `php-rename` handles deterministic callable selection, scoped variable mutation, closure captures, arrow-function captures, supported docblocks, import rewrites, and graph refresh.

## Documentation

Full documentation is in [doc/README.md](doc/README.md).

## Quality

```bash
composer cs
composer analyse
composer test
```
