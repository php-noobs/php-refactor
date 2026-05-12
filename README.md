# PhpRefactor

`PhpRefactor` is the high-level transaction orchestrator for safe PHP refactoring workflows.

It coordinates `php-noobs/php-rename` and `php-noobs/php-retype` operations through one global transaction. It owns transaction lifecycle, source snapshots, rollback, diagnostics aggregation, cross-service graph freshness, and final persistence.

## Installation

```bash
composer require php-noobs/php-refactor
```

When using the PhpNoobs packages from GitHub, configure the VCS repositories:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/php-noobs/member-graph"
        },
        {
            "type": "vcs",
            "url": "https://github.com/php-noobs/php-source-registry"
        },
        {
            "type": "vcs",
            "url": "https://github.com/php-noobs/php-rename"
        },
        {
            "type": "vcs",
            "url": "https://github.com/php-noobs/php-retype"
        }
    ]
}
```

## Usage

```php
use PhpNoobs\PhpRefactor\Application\PhpRefactor;
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

## Supported Type-Change Operations

- `changeMethodParameterType()`
- `changeFunctionParameterType()`
- `changeMethodReturnType()`
- `changeFunctionReturnType()`

`PhpRefactor` owns the cross-service transaction. It calls service step APIs and never nests `PhpRenameTransaction` or `PhpRetypeTransaction`.

## Documentation

Full documentation is in [doc/README.md](doc/README.md).

## Quality

```bash
composer cs
composer analyse
composer test
```
