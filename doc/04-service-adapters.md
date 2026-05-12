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

`RenameServiceAdapter` is the active adapter.

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

Navigation: [Documentation](README.md) | [Previous: Transaction Model](03-transaction-model.md) | [Next: Snapshots And Rollback](05-snapshots-and-rollback.md)
