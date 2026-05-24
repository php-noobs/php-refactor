<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Tests\Integration;

use BabelForge\PhpRefactor\Application\Adapter\RetypeServiceAdapter;
use BabelForge\PhpRefactor\Application\PhpRefactor;
use BabelForge\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use BabelForge\PhpRefactor\Domain\Journal\RefactorActionJournal;
use BabelForge\PhpRefactor\Domain\Transaction\RefactorTransactionStatus;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\TestCase;

/**
 * Covers global transactions backed by php-retype step execution.
 */
final class PhpRefactorRetypeTransactionIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-refactor-retype-'.str_replace('.', '', uniqid('', true));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the temporary integration workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures a global transaction can change method parameter and return types in memory.
     */
    public function testItCommitsMethodParameterAndReturnTypeChangesInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeTypedMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeMethodParameterType('App\\Mailer', 'send', 'message', new Name('Message'), 'Message', 0)
            ->changeMethodReturnType('App\\Mailer', 'send', new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertCount(2, $result->journal);
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('@return SendResult', $printedCode);
        self::assertStringContainsString('function send(Message $message): SendResult', $printedCode);
    }

    /**
     * Ensures function type changes are available through the global transaction.
     */
    public function testItCommitsFunctionParameterAndReturnTypeChangesInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeFunctionParameterType('App\\normalize', 'message', new Name('Message'), 'Message', 0)
            ->changeFunctionReturnType('App\\normalize', new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('@return SendResult', $printedCode);
        self::assertStringContainsString('function normalize(Message $message): SendResult', $printedCode);
    }

    /**
     * Ensures a global transaction can change a property type in memory.
     */
    public function testItCommitsPropertyTypeChangeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeSinglePropertyMailerFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changePropertyType('App\\Mailer', 'transport', new Name('Transport'), 'Transport')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('private Transport $transport;', $printedCode);
    }

    /**
     * Ensures a partial grouped property declaration can be split through the global transaction.
     */
    public function testItReflectsGroupedPropertyPartialRetypeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyMailerFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changePropertyType('App\\Mailer', ['transport', 'backupTransport'], new Name('Transport'), 'Transport')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('private Transport $transport, $backupTransport;', $printedCode);
        self::assertStringContainsString('@var string', $printedCode);
        self::assertStringContainsString('private string $legacyTransport;', $printedCode);
    }

    /**
     * Ensures promoted property types can be changed through the global transaction.
     */
    public function testItCommitsPromotedPropertyTypeChangeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writePromotedPropertyMailerFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changePropertyType('App\\Mailer', 'transport', new Name('Transport'), 'Transport')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('public Transport $transport', $printedCode);
    }

    /**
     * Ensures php-retype consumes the graph updated by a previous property rename step.
     */
    public function testItUsesUpdatedGraphBetweenPropertyRenameAndPropertyRetypeSteps(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeSinglePropertyMailerFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameProperty('App\\Mailer', 'transport', 'primaryTransport')
            ->changePropertyType('App\\Mailer', 'primaryTransport', new Name('Transport'), 'Transport')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('private Transport $primaryTransport;', $printedCode);
        self::assertStringNotContainsString('$transport;', $printedCode);
    }

    /**
     * Ensures a global transaction can change a class constant type in memory.
     */
    public function testItCommitsClassConstantTypeChangeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeConfigFile($srcDirectory.'/Config.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeClassConstantType('App\\Config', 'DEFAULT_PORT', new Identifier('int'), 'int')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@var int', $printedCode);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
    }

    /**
     * Ensures a partial grouped class constant declaration can be split through the global transaction.
     */
    public function testItReflectsGroupedClassConstantPartialRetypeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedConfigFile($srcDirectory.'/Config.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeClassConstantType('App\\Config', 'DEFAULT_PORT', new Identifier('int'), 'int')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('@var int', $printedCode);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
        self::assertStringContainsString('public const string FALLBACK_PORT = \'587\';', $printedCode);
    }

    /**
     * Ensures a global transaction can change an enum backing type in memory.
     */
    public function testItCommitsEnumBackingTypeChangeInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeStatusEnumFile($srcDirectory.'/Status.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeEnumBackingType('App\\Status', new Identifier('int'))
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('enum Status : int', $printedCode);
        self::assertStringNotContainsString('enum Status : string', $printedCode);
    }

    /**
     * Ensures a global transaction can change closure parameter and return types inside a method.
     */
    public function testItCommitsClosureParameterAndReturnTypeChangesInsideMethodInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeNestedMethodMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeClosureParameterTypeInMethod('App\\Mailer', 'send', 0, 'message', new Name('Message'), 'Message', 0)
            ->changeClosureReturnTypeInMethod('App\\Mailer', 'send', 0, new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('function (Message $message): SendResult', $printedCode);
    }

    /**
     * Ensures a global transaction can change arrow-function parameter and return types inside a function.
     */
    public function testItCommitsArrowFunctionParameterAndReturnTypeChangesInsideFunctionInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeNestedFunctionFile($srcDirectory.'/functions.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeArrowFunctionParameterTypeInFunction('App\\pipe', 0, 'message', new Name('Message'), 'Message', 0)
            ->changeArrowFunctionReturnTypeInFunction('App\\pipe', 0, new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('fn(Message $message): SendResult =>', $printedCode);
    }

    /**
     * Ensures a global transaction can change nested callable types inside a file container.
     */
    public function testItCommitsNestedCallableTypeChangesInsideFileInMemory(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $filePath = $srcDirectory.'/file-callables.php';
        $this->writeFileLevelCallableFile($filePath);
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeClosureParameterTypeInFile($filePath, 0, 'message', new Name('Message'), 'Message', 0)
            ->changeClosureReturnTypeInFile($filePath, 0, new Identifier('int'), 'int')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('function (Message $message): int', $printedCode);
    }

    /**
     * Ensures php-retype consumes the graph updated by a previous class constant rename step.
     */
    public function testItUsesUpdatedGraphBetweenClassConstantRenameAndRetypeSteps(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeConfigFile($srcDirectory.'/Config.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameClassConstant('App\\Config', 'DEFAULT_PORT', 'SMTP_PORT')
            ->changeClassConstantType('App\\Config', 'SMTP_PORT', new Identifier('int'), 'int')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('public const int SMTP_PORT = 25;', $printedCode);
        self::assertStringNotContainsString('DEFAULT_PORT', $printedCode);
    }

    /**
     * Ensures php-retype consumes the graph updated by a previous php-rename step.
     */
    public function testItUsesUpdatedGraphBetweenRenameAndRetypeSteps(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeTypedMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->changeMethodReturnType('App\\Mailer', 'deliver', new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('function deliver(string $message): SendResult', $printedCode);
        self::assertStringNotContainsString('function send(', $printedCode);
    }

    /**
     * Ensures php-retype consumes nested callable containers updated by a previous php-rename step.
     */
    public function testItUsesUpdatedGraphBetweenRenameAndNestedCallableRetypeSteps(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeNestedMethodMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->changeClosureReturnTypeInMethod('App\\Mailer', 'deliver', 0, new Name('SendResult'), 'SendResult')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('function deliver(): void', $printedCode);
        self::assertStringContainsString('function (string $message): SendResult', $printedCode);
        self::assertStringNotContainsString('function send(', $printedCode);
    }

    /**
     * Ensures php-retype diagnostics are mapped with the retype service name.
     */
    public function testItMapsRetypeDiagnosticsWithRetypeServiceName(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeTypedMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeMethodReturnType('App\\Mailer', 'missing', new Name('SendResult'), 'SendResult')
            ->commit();

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertCount(1, $result->diagnostics);
        self::assertTrue($this->hasDiagnosticFromService($result->diagnostics, RetypeServiceAdapter::SERVICE));
    }

    /**
     * Ensures a failed mixed workflow restores the begin-transaction virtual files.
     */
    public function testItRollsBackVirtualFilesAfterFailingMixedWorkflow(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeTypedMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->changeMethodReturnType('App\\Mailer', 'deliver', new NullableType(new Identifier('void')), '?void')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('function send(string $message): string', $printedCode);
        self::assertStringNotContainsString('function deliver(', $printedCode);
    }

    /**
     * Ensures a failed workflow with property retype restores the begin-transaction virtual files.
     */
    public function testItRollsBackVirtualFilesAfterFailingPropertyRetypeWorkflow(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeSinglePropertyMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameProperty('App\\Mailer', 'transport', 'primaryTransport')
            ->changePropertyType('App\\Mailer', 'primaryTransport', new NullableType(new Identifier('void')), '?void')
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('private string $transport;', $printedCode);
        self::assertStringNotContainsString('$primaryTransport', $printedCode);
    }

    /**
     * Ensures a failing workflow with nested callable retype restores the begin-transaction virtual files.
     */
    public function testItRollsBackVirtualFilesAfterFailingNestedCallableRetypeWorkflow(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeNestedMethodMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->changeClosureParameterTypeInMethod('App\\Mailer', 'deliver', 0, 'message', new NullableType(new Identifier('void')), '?void', 0)
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('function send(): void', $printedCode);
        self::assertStringContainsString('function (string $message): string', $printedCode);
        self::assertStringNotContainsString('function deliver(', $printedCode);
    }

    /**
     * Ensures a failing workflow with enum backing retype restores the begin-transaction virtual files.
     */
    public function testItRollsBackVirtualFilesAfterFailingEnumBackingRetypeWorkflow(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeConfigFile($srcDirectory.'/Config.php');
        $this->writeStatusEnumFile($srcDirectory.'/Status.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->renameClassConstant('App\\Config', 'DEFAULT_PORT', 'SMTP_PORT')
            ->changeEnumBackingType('App\\Status', new Identifier('bool'))
            ->commit();

        $printedCode = $this->printedCode($result->finalBuild->virtualFiles);

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertFalse($result->isSuccessful());
        self::assertTrue($result->diagnostics->hasErrors());
        self::assertStringContainsString('public const string DEFAULT_PORT = 25;', $printedCode);
        self::assertStringNotContainsString('SMTP_PORT', $printedCode);
        self::assertStringContainsString('enum Status : string', $printedCode);
    }

    /**
     * Ensures unexpected php-retype failures are journaled under the retype service.
     */
    public function testItMapsRetypeExceptionsWithRetypeServiceName(): void
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);
        $this->writeTypedMailerFile($srcDirectory.'/Mailer.php');

        $result = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )
            ->beginTransaction()
            ->changeMethodReturnType('App\\Mailer', 'send', new NullableType(new Identifier('void')), '?void')
            ->commit();

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertTrue($result->diagnostics->hasErrors());
        self::assertTrue($this->hasDiagnosticFromService($result->diagnostics, RetypeServiceAdapter::SERVICE));
        self::assertTrue($this->hasJournalEntryFromService($result->journal, RetypeServiceAdapter::SERVICE));
    }

    /**
     * Writes the typed mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeTypedMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Message
            {
            }

            final class SendResult
            {
            }

            final class Mailer
            {
                /**
                 * @param string $message
                 *
                 * @return string
                 */
                public function send(string $message): string
                {
                    return $message;
                }
            }
            PHP);
    }

    /**
     * Writes the function fixture.
     *
     * @param string $filePath the file path
     */
    private function writeFunctionFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            /**
             * @param string $message
             *
             * @return string
             */
            function normalize(string $message): string
            {
                return $message;
            }
            PHP);
    }

    /**
     * Writes the nested method mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeNestedMethodMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Message
            {
            }

            final class SendResult
            {
            }

            final class Mailer
            {
                public function send(): void
                {
                    $formatter = function (string $message): string {
                        return $message;
                    };
                    $formatter('hello');
                }
            }
            PHP);
    }

    /**
     * Writes the nested function fixture.
     *
     * @param string $filePath the file path
     */
    private function writeNestedFunctionFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            function pipe(): void
            {
                $formatter = fn (string $message): string => $message;
                $formatter('hello');
            }
            PHP);
    }

    /**
     * Writes the file-level callable fixture.
     *
     * @param string $filePath the file path
     */
    private function writeFileLevelCallableFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            $formatter = function (string $message): string {
                return $message;
            };
            $formatter('hello');
            PHP);
    }

    /**
     * Writes the single property mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSinglePropertyMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                /**
                 * @var string
                 */
                private string $transport;
            }
            PHP);
    }

    /**
     * Writes the grouped property mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedPropertyMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                /**
                 * @var string
                 */
                private string $transport, $backupTransport, $legacyTransport;
            }
            PHP);
    }

    /**
     * Writes the promoted property mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writePromotedPropertyMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function __construct(
                    /**
                     * @var string
                     */
                    public string $transport,
                ) {
                }
            }
            PHP);
    }

    /**
     * Writes the transport fixture.
     *
     * @param string $filePath the file path
     */
    private function writeTransportFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Transport
            {
            }
            PHP);
    }

    /**
     * Writes the config fixture.
     *
     * @param string $filePath the file path
     */
    private function writeConfigFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Config
            {
                /**
                 * @var string
                 */
                public const string DEFAULT_PORT = 25;
            }
            PHP);
    }

    /**
     * Writes the grouped config fixture.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedConfigFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Config
            {
                /**
                 * @var string
                 */
                public const string DEFAULT_PORT = 25, FALLBACK_PORT = '587';
            }
            PHP);
    }

    /**
     * Writes the status enum fixture.
     *
     * @param string $filePath the file path
     */
    private function writeStatusEnumFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            enum Status: string
            {
                case Active = '1';
            }
            PHP);
    }

    /**
     * Writes the message fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMessageFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Message
            {
            }
            PHP);
    }

    /**
     * Writes the send result fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSendResultFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class SendResult
            {
            }
            PHP);
    }

    /**
     * Prints all virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to print
     */
    private function printedCode(VirtualPhpSourceFileCollection $virtualFiles): string
    {
        $code = '';

        foreach ($virtualFiles as $virtualFile) {
            $code .= $virtualFile->print($virtualFile->nodes)."\n";
        }

        return $code;
    }

    /**
     * Indicates whether a diagnostic collection contains a diagnostic from one service.
     *
     * @param RefactorDiagnosticCollection $diagnostics the diagnostics to inspect
     * @param string                       $service     the expected service name
     */
    private function hasDiagnosticFromService(RefactorDiagnosticCollection $diagnostics, string $service): bool
    {
        foreach ($diagnostics as $diagnostic) {
            if ($service === $diagnostic->service) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether a journal contains an entry from one service.
     *
     * @param RefactorActionJournal $journal the journal to inspect
     * @param string                $service the expected service name
     */
    private function hasJournalEntryFromService(RefactorActionJournal $journal, string $service): bool
    {
        foreach ($journal as $entry) {
            if ($service === $entry->service) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes one directory recursively.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (false === is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
