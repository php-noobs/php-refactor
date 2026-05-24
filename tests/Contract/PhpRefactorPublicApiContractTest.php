<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Tests\Contract;

use BabelForge\PhpRefactor\Application\Adapter\RenameServiceAdapter;
use BabelForge\PhpRefactor\Application\PhpRefactor;
use BabelForge\PhpRefactor\Application\PhpRefactorTransaction;
use BabelForge\PhpRefactor\Domain\Transaction\RefactorTransactionResult;
use BabelForge\PhpRename\Application\PhpRename;
use PHPUnit\Framework\TestCase;

/**
 * Locks the public PhpRefactor API surface.
 */
final class PhpRefactorPublicApiContractTest extends TestCase
{
    /**
     * Ensures the public facade exposes stable construction and transaction entry points.
     */
    public function testFacadePublicApiIsStable(): void
    {
        $facade = new \ReflectionClass(PhpRefactor::class);

        self::assertTrue($facade->hasMethod('fromDirectory'));
        self::assertTrue($facade->getMethod('fromDirectory')->isPublic());
        self::assertTrue($facade->hasMethod('fromBuild'));
        self::assertTrue($facade->getMethod('fromBuild')->isPublic());
        self::assertTrue($facade->hasMethod('beginTransaction'));
        self::assertTrue($facade->getMethod('beginTransaction')->isPublic());
    }

    /**
     * Ensures the transaction exposes every php-rename step supported by php-refactor.
     */
    public function testTransactionRenamePublicApiIsStable(): void
    {
        $transaction = new \ReflectionClass(PhpRefactorTransaction::class);

        foreach ($this->renameMethods() as $methodName) {
            self::assertTrue($transaction->hasMethod($methodName), sprintf('Missing method %s.', $methodName));
            self::assertTrue($transaction->getMethod($methodName)->isPublic(), sprintf('Method %s is not public.', $methodName));
        }
    }

    /**
     * Ensures every public php-rename step API has a mapped adapter operation.
     */
    public function testRenameAdapterCoversEveryPhpRenameStepApi(): void
    {
        $renamer = new \ReflectionClass(PhpRename::class);
        $adapter = new \ReflectionClass(RenameServiceAdapter::class);

        foreach ($this->renameStepAdapterMap() as $stepMethodName => $adapterMethodName) {
            self::assertTrue($renamer->hasMethod($stepMethodName), sprintf('Missing php-rename step method %s.', $stepMethodName));
            self::assertTrue($renamer->getMethod($stepMethodName)->isPublic(), sprintf('php-rename step method %s is not public.', $stepMethodName));
            self::assertTrue($adapter->hasMethod($adapterMethodName), sprintf('Missing adapter method %s for %s.', $adapterMethodName, $stepMethodName));
            self::assertTrue($adapter->getMethod($adapterMethodName)->isPublic(), sprintf('Adapter method %s is not public.', $adapterMethodName));
        }

        self::assertSame(array_keys($this->renameStepAdapterMap()), $this->publicRenameStepMethods($renamer));
    }

    /**
     * Ensures the transaction exposes every php-retype step supported by php-refactor.
     */
    public function testTransactionRetypePublicApiIsStable(): void
    {
        $transaction = new \ReflectionClass(PhpRefactorTransaction::class);

        foreach ($this->retypeMethods() as $methodName) {
            self::assertTrue($transaction->hasMethod($methodName), sprintf('Missing method %s.', $methodName));
            self::assertTrue($transaction->getMethod($methodName)->isPublic(), sprintf('Method %s is not public.', $methodName));
        }
    }

    /**
     * Ensures transaction completion methods return the global transaction result.
     */
    public function testTransactionCompletionPublicApiIsStable(): void
    {
        $transaction = new \ReflectionClass(PhpRefactorTransaction::class);

        foreach (['commit', 'commitAndSave', 'commitAndSaveSourceFile', 'rollback', 'result'] as $methodName) {
            $method = $transaction->getMethod($methodName);

            self::assertTrue($method->isPublic());
            self::assertSame(RefactorTransactionResult::class, (string) $method->getReturnType());
        }
    }

    /**
     * Returns the rename methods exposed by the global transaction.
     *
     * @return list<string>
     */
    private function renameMethods(): array
    {
        return [
            'executeRenamePlan',
            'renameClass',
            'renameClassFqcn',
            'renameMethod',
            'renameProperty',
            'renameClassConstant',
            'renameEnumCase',
            'renameFunction',
            'renameFunctionFqcn',
            'renameConstant',
            'renameConstantFqcn',
            'renameMethodParameter',
            'renameFunctionParameter',
            'renameNestedCallableParameter',
            'renameClosureParameterInMethod',
            'renameArrowFunctionParameterInMethod',
            'renameClosureParameterInFunction',
            'renameArrowFunctionParameterInFunction',
            'renameClosureParameterInFile',
            'renameArrowFunctionParameterInFile',
            'renameNestedCallableLocalVariable',
            'renameClosureLocalVariableInMethod',
            'renameArrowFunctionLocalVariableInMethod',
            'renameClosureLocalVariableInFunction',
            'renameArrowFunctionLocalVariableInFunction',
            'renameClosureLocalVariableInFile',
            'renameArrowFunctionLocalVariableInFile',
        ];
    }

    /**
     * Returns the expected mapping between php-rename step methods and adapter methods.
     *
     * @return array<string, string>
     */
    private function renameStepAdapterMap(): array
    {
        return [
            'executeStep' => 'executeStep',
            'executeStepMethodRename' => 'renameMethod',
            'executeStepPropertyRename' => 'renameProperty',
            'executeStepClassConstantRename' => 'renameClassConstant',
            'executeStepEnumCaseRename' => 'renameEnumCase',
            'executeStepClassRename' => 'renameClass',
            'executeStepClassFqcnRename' => 'renameClassFqcn',
            'executeStepFunctionRename' => 'renameFunction',
            'executeStepFunctionFqcnRename' => 'renameFunctionFqcn',
            'executeStepConstantRename' => 'renameConstant',
            'executeStepConstantFqcnRename' => 'renameConstantFqcn',
            'executeStepMethodParameterRename' => 'renameMethodParameter',
            'executeStepFunctionParameterRename' => 'renameFunctionParameter',
            'executeStepNestedCallableLocalVariableRename' => 'renameNestedCallableLocalVariable',
            'executeStepClosureLocalVariableRenameInMethod' => 'renameClosureLocalVariableInMethod',
            'executeStepArrowFunctionLocalVariableRenameInMethod' => 'renameArrowFunctionLocalVariableInMethod',
            'executeStepClosureLocalVariableRenameInFunction' => 'renameClosureLocalVariableInFunction',
            'executeStepArrowFunctionLocalVariableRenameInFunction' => 'renameArrowFunctionLocalVariableInFunction',
            'executeStepClosureLocalVariableRenameInFile' => 'renameClosureLocalVariableInFile',
            'executeStepArrowFunctionLocalVariableRenameInFile' => 'renameArrowFunctionLocalVariableInFile',
            'executeStepNestedCallableParameterRename' => 'renameNestedCallableParameter',
            'executeStepClosureParameterRenameInMethod' => 'renameClosureParameterInMethod',
            'executeStepArrowFunctionParameterRenameInMethod' => 'renameArrowFunctionParameterInMethod',
            'executeStepClosureParameterRenameInFunction' => 'renameClosureParameterInFunction',
            'executeStepArrowFunctionParameterRenameInFunction' => 'renameArrowFunctionParameterInFunction',
            'executeStepClosureParameterRenameInFile' => 'renameClosureParameterInFile',
            'executeStepArrowFunctionParameterRenameInFile' => 'renameArrowFunctionParameterInFile',
        ];
    }

    /**
     * Returns public php-rename step methods in reflection order.
     *
     * @param \ReflectionClass<PhpRename> $renamer the php-rename reflection class
     *
     * @return list<string>
     */
    private function publicRenameStepMethods(\ReflectionClass $renamer): array
    {
        $methodNames = [];

        foreach ($renamer->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (0 !== strncmp($method->getName(), 'executeStep', strlen('executeStep'))) {
                continue;
            }

            $methodNames[] = $method->getName();
        }

        return $methodNames;
    }

    /**
     * Returns the retype methods exposed by the global transaction.
     *
     * @return list<string>
     */
    private function retypeMethods(): array
    {
        return [
            'changeMethodParameterType',
            'changeFunctionParameterType',
            'changeMethodReturnType',
            'changeFunctionReturnType',
            'changePropertyType',
            'changeClassConstantType',
            'changeEnumBackingType',
            'changeClosureParameterTypeInMethod',
            'changeClosureReturnTypeInMethod',
            'changeArrowFunctionParameterTypeInMethod',
            'changeArrowFunctionReturnTypeInMethod',
            'changeClosureParameterTypeInFunction',
            'changeClosureReturnTypeInFunction',
            'changeArrowFunctionParameterTypeInFunction',
            'changeArrowFunctionReturnTypeInFunction',
            'changeClosureParameterTypeInFile',
            'changeClosureReturnTypeInFile',
            'changeArrowFunctionParameterTypeInFile',
            'changeArrowFunctionReturnTypeInFile',
        ];
    }
}
