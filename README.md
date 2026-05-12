# PhpRefactor

`PhpRefactor` is the high-level transaction orchestrator for safe PHP refactoring workflows.

It currently coordinates `php-noobs/php-rename` operations through one global transaction. It owns transaction lifecycle, source snapshots, rollback, diagnostics aggregation, and final persistence.

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
        }
    ]
}
```

## Usage

```php
use PhpNoobs\PhpRefactor\Application\PhpRefactor;

$refactor = PhpRefactor::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);

$result = $refactor
    ->beginTransaction()
    ->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender')
    ->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver')
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

## Documentation

Full documentation is in [doc/README.md](doc/README.md).

## Quality

```bash
composer cs
composer analyse
composer test
```
