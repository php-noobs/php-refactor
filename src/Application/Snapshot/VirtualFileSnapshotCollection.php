<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Application\Snapshot;

use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Collection of virtual file snapshots.
 *
 * @implements \IteratorAggregate<VirtualFileSnapshot>
 */
final class VirtualFileSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, VirtualFileSnapshot>
     */
    private array $snapshots = [];

    /**
     * Adds a snapshot.
     *
     * @param VirtualFileSnapshot $snapshot the snapshot to add
     */
    public function add(VirtualFileSnapshot $snapshot): self
    {
        $this->snapshots[$snapshot->virtualFilePath] = $snapshot;

        return $this;
    }

    /**
     * Restores all matching virtual files from snapshots.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to restore
     */
    public function restore(VirtualPhpSourceFileCollection $virtualFiles): void
    {
        foreach ($virtualFiles as $virtualFile) {
            $snapshot = $this->snapshots[$virtualFile->virtualFilePath] ?? null;

            if (null === $snapshot) {
                continue;
            }

            $snapshot->restore($virtualFile);
        }
    }

    /**
     * Returns the snapshot iterator.
     *
     * @return \Traversable<VirtualFileSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->snapshots);
    }

    /**
     * Counts snapshots.
     */
    public function count(): int
    {
        return count($this->snapshots);
    }
}
