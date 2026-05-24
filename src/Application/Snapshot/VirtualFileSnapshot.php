<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Application\Snapshot;

use BabelForge\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;

/**
 * Stores the source state of one virtual PHP file.
 */
final readonly class VirtualFileSnapshot
{
    /**
     * Constructor.
     *
     * @param string      $virtualFilePath the virtual file path
     * @param string      $code            the original printed code
     * @param array<Node> $nodes           the original transformed AST nodes
     * @param array<Node> $originalNodes   the original non-transformed AST nodes
     * @param bool        $isUpdated       whether the virtual file was marked updated
     */
    private function __construct(
        public string $virtualFilePath,
        private string $code,
        private array $nodes,
        private array $originalNodes,
        private bool $isUpdated,
    ) {
    }

    /**
     * Creates a snapshot from one virtual file.
     *
     * @param VirtualPhpSourceFile $virtualFile the virtual file to snapshot
     */
    public static function fromVirtualFile(VirtualPhpSourceFile $virtualFile): self
    {
        return new self(
            virtualFilePath: $virtualFile->virtualFilePath,
            code: $virtualFile->code,
            nodes: self::deepCopyNodes($virtualFile->nodes),
            originalNodes: self::deepCopyNodes($virtualFile->originalNonTransformedNodes),
            isUpdated: $virtualFile->isUpdated,
        );
    }

    /**
     * Restores this snapshot onto one virtual file.
     *
     * @param VirtualPhpSourceFile $virtualFile the virtual file to restore
     */
    public function restore(VirtualPhpSourceFile $virtualFile): void
    {
        $virtualFile->code = $this->code;
        $virtualFile->nodes = self::deepCopyNodes($this->nodes);
        $virtualFile->originalNonTransformedNodes = self::deepCopyNodes($this->originalNodes);
        $virtualFile->isUpdated = $this->isUpdated;
    }

    /**
     * Deep-copies PHPParser nodes through serialization.
     *
     * @param array<Node> $nodes the nodes to copy
     *
     * @return array<Node>
     */
    private static function deepCopyNodes(array $nodes): array
    {
        /** @var array<Node> $copy */
        $copy = unserialize(serialize($nodes));

        return $copy;
    }
}
