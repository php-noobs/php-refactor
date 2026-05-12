# Service Adapters

Navigation: [Documentation](README.md) | [Previous: Transaction Model](03-transaction-model.md) | [Next: Snapshots And Rollback](05-snapshots-and-rollback.md)

Adapters isolate `php-refactor` from service-specific step contracts.

The flow is:

```text
RefactorTransactionContext
    -> ServiceSpecificStepContext
    -> executeStep...
    -> ServiceSpecificStepResult
    -> RefactorTransactionContext
```

## Rename Adapter

`RenameServiceAdapter` adapts `php-rename`.

It maps the global context to `php-rename`'s `RenameStepContext`, calls transaction-neutral `executeStep...Rename()` methods, maps `RenameStepResult` back into the global context, and stores the returned rename context as service-specific state.

The supported operations mirror the `php-rename` orchestrable step API:

- `renameClass()`;
- `renameClassFqcn()`;
- `renameMethod()`;
- `renameProperty()`;
- `renameClassConstant()`;
- `renameEnumCase()`;
- `renameFunction()`;
- `renameFunctionFqcn()`;
- `renameConstant()`;
- `renameConstantFqcn()`;
- `renameMethodParameter()`;
- `renameFunctionParameter()`.

`php-refactor` does not call `PhpRenameTransaction`. That transaction belongs to standalone `php-rename` usage only.

## Retype Adapter

`RetypeServiceAdapter` adapts `php-retype`.

It maps the global context to `php-retype`'s `RetypeStepContext`, calls transaction-neutral `executeStep...TypeChange()` methods, maps `RetypeStepResult` back into the global context, updates the global current build from `$step->context->currentBuild`, and stores the returned retype context as service-specific state.

The supported operations mirror the `php-retype` orchestrable step API:

- `changeMethodParameterType()`;
- `changeFunctionParameterType()`;
- `changeMethodReturnType()`;
- `changeFunctionReturnType()`;
- `changePropertyType()`.

Property type changes include single properties, grouped properties, partial grouped declaration splitting, promoted properties, and direct `@var` updates. Those behaviors are provided by `php-retype`; the adapter only maps the global transaction context into the retype step API and maps the result back.

`php-refactor` does not call `PhpRetypeTransaction`. That transaction belongs to standalone `php-retype` usage only.

## Mixed Workflows

Adapters are called in the order chosen by the transaction chain. Each adapter receives the current global build, so a later service sees semantic changes made by an earlier service.

For example, this workflow renames a method through `php-rename`, then changes the renamed method return type through `php-retype`:

```php
use PhpParser\Node\Name;

$result = $refactor
    ->beginTransaction()
    ->renameMethod('App\\Mailer', 'send', 'deliver')
    ->changeMethodReturnType('App\\Mailer', 'deliver', new Name('DeliveryResult'), 'DeliveryResult')
    ->commitAndSave();
```

Navigation: [Documentation](README.md) | [Previous: Transaction Model](03-transaction-model.md) | [Next: Snapshots And Rollback](05-snapshots-and-rollback.md)
