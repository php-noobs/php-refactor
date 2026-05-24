<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Tests\Integration;

use BabelForge\PhpRefactor\Application\PhpRefactor;
use BabelForge\PhpRefactor\Domain\Transaction\RefactorTransactionResult;
use BabelForge\PhpRefactor\Domain\Transaction\RefactorTransactionStatus;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers every rename operation exposed by the global refactor transaction.
 */
final class PhpRefactorRenameOperationIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-refactor-rename-operation-'.str_replace('.', '', uniqid('', true));
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
     * Ensures short class renaming is delegated through the global transaction.
     */
    public function testItRenamesClass(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/ClassFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
            }

            final class Runner
            {
                public function run(): Mailer
                {
                    return new Mailer();
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameClass('App\\Mailer', 'Sender')
            ->commit());

        self::assertStringContainsString('class Sender', $code);
        self::assertStringContainsString('new Sender()', $code);
        self::assertStringNotContainsString('class Mailer', $code);
    }

    /**
     * Ensures property renaming is delegated through the global transaction.
     */
    public function testItRenamesProperty(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/PropertyFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public string $transport = 'smtp';

                public function transport(): string
                {
                    return $this->transport;
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameProperty('App\\Mailer', 'transport', 'mailerTransport')
            ->commit());

        self::assertStringContainsString('$mailerTransport', $code);
        self::assertStringContainsString('$this->mailerTransport', $code);
        self::assertStringNotContainsString('$this->transport', $code);
    }

    /**
     * Ensures class-constant renaming is delegated through the global transaction.
     */
    public function testItRenamesClassConstant(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/ClassConstantFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public const DEFAULT_TRANSPORT = 'smtp';

                public function transport(): string
                {
                    return self::DEFAULT_TRANSPORT;
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'FALLBACK_TRANSPORT')
            ->commit());

        self::assertStringContainsString('FALLBACK_TRANSPORT', $code);
        self::assertStringNotContainsString('DEFAULT_TRANSPORT', $code);
    }

    /**
     * Ensures enum-case renaming is delegated through the global transaction.
     */
    public function testItRenamesEnumCase(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/EnumFixture.php', <<<'PHP'
            <?php

            namespace App;

            enum Status
            {
                case ACTIVE;
            }

            final class Runner
            {
                public function run(): Status
                {
                    return Status::ACTIVE;
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameEnumCase('App\\Status', 'ACTIVE', 'ENABLED')
            ->commit());

        self::assertStringContainsString('case ENABLED', $code);
        self::assertStringContainsString('Status::ENABLED', $code);
        self::assertStringNotContainsString('ACTIVE', $code);
    }

    /**
     * Ensures short function renaming is delegated through the global transaction.
     */
    public function testItRenamesFunction(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/FunctionFixture.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }

            final class Runner
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameFunction('App\\send_mail', 'deliver_mail')
            ->commit());

        self::assertStringContainsString('function deliver_mail()', $code);
        self::assertStringContainsString('return deliver_mail()', $code);
        self::assertStringNotContainsString('send_mail', $code);
    }

    /**
     * Ensures function FQCN renaming is delegated through the global transaction.
     */
    public function testItRenamesFunctionFqcn(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/FunctionFqcnFixture.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }

            namespace Client;

            final class Runner
            {
                public function run(): string
                {
                    return \App\send_mail();
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail')
            ->commit());

        self::assertStringContainsString('namespace Tools', $code);
        self::assertStringContainsString('function deliver_mail()', $code);
        self::assertStringContainsString('deliver_mail()', $code);
        self::assertStringNotContainsString('send_mail', $code);
    }

    /**
     * Ensures short namespace-level constant renaming is delegated through the global transaction.
     */
    public function testItRenamesConstant(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/ConstantFixture.php', <<<'PHP'
            <?php

            namespace App;

            const ENABLED = true;

            final class Runner
            {
                public function run(): bool
                {
                    return ENABLED;
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameConstant('App\\ENABLED', 'ACTIVE')
            ->commit());

        self::assertStringContainsString('const ACTIVE', $code);
        self::assertStringContainsString('return ACTIVE', $code);
        self::assertStringNotContainsString('ENABLED', $code);
    }

    /**
     * Ensures namespace-level constant FQCN renaming is delegated through the global transaction.
     */
    public function testItRenamesConstantFqcn(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/ConstantFqcnFixture.php', <<<'PHP'
            <?php

            namespace App;

            const ENABLED = true;

            namespace Client;

            final class Runner
            {
                public function run(): bool
                {
                    return \App\ENABLED;
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameConstantFqcn('App\\ENABLED', 'Tools\\ACTIVE')
            ->commit());

        self::assertStringContainsString('namespace Tools', $code);
        self::assertStringContainsString('const ACTIVE', $code);
        self::assertStringContainsString('ACTIVE', $code);
        self::assertStringNotContainsString('ENABLED', $code);
    }

    /**
     * Ensures method parameter renaming is delegated through the global transaction.
     */
    public function testItRenamesMethodParameter(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/MethodParameterFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(string $message): string
                {
                    return $message;
                }
            }

            final class Runner
            {
                public function run(Mailer $mailer): string
                {
                    return $mailer->send(message: 'hello');
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameMethodParameter('App\\Mailer', 'send', 'message', 'emailMessage', 0)
            ->commit());

        self::assertStringContainsString('$emailMessage', $code);
        self::assertStringContainsString('emailMessage: \'hello\'', $code);
        self::assertStringNotContainsString('$message', $code);
        self::assertStringNotContainsString('message:', $code);
    }

    /**
     * Ensures function parameter renaming is delegated through the global transaction.
     */
    public function testItRenamesFunctionParameter(): void
    {
        $srcDirectory = $this->createSourceDirectory();
        $this->writeFile($srcDirectory.'/FunctionParameterFixture.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(string $message): string
            {
                return $message;
            }

            final class Runner
            {
                public function run(): string
                {
                    return send_mail(message: 'hello');
                }
            }
            PHP);

        $code = $this->commitCode($srcDirectory, static fn (PhpRefactor $refactor) => $refactor
            ->beginTransaction()
            ->renameFunctionParameter('App\\send_mail', 'message', 'emailMessage', 0)
            ->commit());

        self::assertStringContainsString('$emailMessage', $code);
        self::assertStringContainsString('emailMessage: \'hello\'', $code);
        self::assertStringNotContainsString('$message', $code);
        self::assertStringNotContainsString('message:', $code);
    }

    /**
     * Creates the source directory.
     */
    private function createSourceDirectory(): string
    {
        $srcDirectory = $this->workspace.'/src';
        mkdir($srcDirectory, 0o777, true);

        return $srcDirectory;
    }

    /**
     * Executes a refactor transaction and returns printed final code.
     *
     * @param string                                           $srcDirectory the source directory
     * @param callable(PhpRefactor): RefactorTransactionResult $callback     the transaction callback
     */
    private function commitCode(string $srcDirectory, callable $callback): string
    {
        $refactor = PhpRefactor::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );
        $result = $callback($refactor);

        self::assertObjectHasProperty('status', $result);
        self::assertSame(RefactorTransactionStatus::COMMITTED, $result->status);
        self::assertObjectHasProperty('diagnostics', $result);
        self::assertCount(0, $result->diagnostics);
        self::assertObjectHasProperty('finalBuild', $result);

        return $this->printedCode($result->finalBuild->virtualFiles);
    }

    /**
     * Writes one fixture file.
     *
     * @param string $filePath the file path
     * @param string $code     the PHP code
     */
    private function writeFile(string $filePath, string $code): void
    {
        file_put_contents($filePath, $code);
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
