<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Tests\Integration;

use PhpNoobs\PhpRefactor\Application\Adapter\RetypeServiceAdapter;
use PhpNoobs\PhpRefactor\Application\PhpRefactor;
use PhpNoobs\PhpRefactor\Domain\Diagnostic\RefactorDiagnosticCollection;
use PhpNoobs\PhpRefactor\Domain\Journal\RefactorActionJournal;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
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
