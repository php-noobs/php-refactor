<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Tests\Contract;

use PhpNoobs\PhpRefactor\Application\PhpRefactor;
use PhpNoobs\PhpRefactor\Application\PhpRefactorTransaction;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionResult;
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
        ];
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
