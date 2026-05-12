<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Tests\Integration;

use PhpNoobs\PhpRefactor\Application\PhpRefactor;
use PhpNoobs\PhpRefactor\Domain\Transaction\RefactorTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers the first global transaction slice backed by php-rename step execution.
 */
final class PhpRefactorRenameTransactionIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-refactor-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures a global transaction can commit a rename in memory without saving physical files.
     */
    public function testItCommitsRenameMethodInMemoryWithoutSavingPhysicalFiles(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Mailer.php';
        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($filePath);

        $refactor = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        $result = $refactor
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->commit();

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertCount(1, $result->journal);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('function deliver(', $this->printedCode($result->finalBuild->virtualFiles));
        self::assertStringContainsString('$this->deliver()', $this->printedCode($result->finalBuild->virtualFiles));
        self::assertStringContainsString('function send(', (string) file_get_contents($filePath));
    }

    /**
     * Ensures a global transaction saves through the final source registry.
     */
    public function testItCommitsAndSavesRenameMethodThroughSourceRegistry(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Mailer.php';
        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($filePath);

        $refactor = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        $result = $refactor
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'deliver')
            ->commitAndSave();

        $savedCode = (string) file_get_contents($filePath);

        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('function deliver(', $savedCode);
        self::assertStringContainsString('$this->deliver()', $savedCode);
        self::assertStringNotContainsString('function send(', $savedCode);
    }

    /**
     * Ensures a blocking rename diagnostic rolls back the global transaction.
     */
    public function testItRollsBackGlobalSnapshotWhenRenameReportsBlockingDiagnostics(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Mailer.php';
        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($filePath);

        $refactor = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        $result = $refactor
            ->beginTransaction()
            ->renameMethod('App\\Mailer', 'send', 'call')
            ->commit();

        self::assertSame(RefactorTransactionStatus::ROLLED_BACK, $result->status);
        self::assertFalse($result->isSuccessful());
        self::assertTrue($result->diagnostics->hasErrors());
        self::assertStringContainsString('function send(', $this->printedCode($result->finalBuild->virtualFiles));
        self::assertStringContainsString('function call(', $this->printedCode($result->finalBuild->virtualFiles));
        self::assertStringContainsString('function send(', (string) file_get_contents($filePath));
    }

    /**
     * Writes the mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                    $this->send();
                }

                public function call(): void
                {
                }
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
