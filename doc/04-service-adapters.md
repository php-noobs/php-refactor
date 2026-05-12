# Service Adapters

Navigation: [Documentation](README.md) | [Previous: Transaction Model](03-transaction-model.md) | [Next: Snapshots And Rollback](05-snapshots-and-rollback.md)

Adapters isolate `php-refactor` from service-specific step contracts.

The intended flow is:

```text
RefactorTransactionContext
    -> ServiceSpecificStepContext
    -> executeStep...
    -> ServiceSpecificStepResult
    -> RefactorTransactionContext
```

## Rename Adapter

`RenameServiceAdapter` is the first adapter.

It maps the global context to `php-rename`'s `RenameStepContext`, calls transaction-neutral `executeStep...Rename()` methods, maps `RenameStepResult` back into the global context, and stores the returned rename context as service-specific state.

The first supported operations are:

- `renameClassFqcn()`;
- `renameMethod()`.

`php-refactor` does not call `PhpRenameTransaction`. That transaction belongs to standalone `php-rename` usage only.

## Future Adapters

Future adapters should follow the same shape without forcing a shared contract package too early:

- `RetypeServiceAdapter`;
- `CloneServiceAdapter`;
- `MoveServiceAdapter`;
- `ExtractServiceAdapter`;
- `InlineServiceAdapter`.

Each specialized service may expose its own step context and result objects. `php-refactor` owns the adaptation layer and the global orchestration policy.

Navigation: [Documentation](README.md) | [Previous: Transaction Model](03-transaction-model.md) | [Next: Snapshots And Rollback](05-snapshots-and-rollback.md)
