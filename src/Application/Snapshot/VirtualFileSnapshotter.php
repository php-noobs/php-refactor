<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application\Snapshot;

use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Captures global source snapshots for a refactor transaction.
 */
final readonly class VirtualFileSnapshotter
{
    /**
     * Captures all virtual files in one collection.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to snapshot
     */
    public function snapshot(VirtualPhpSourceFileCollection $virtualFiles): VirtualFileSnapshotCollection
    {
        $snapshots = new VirtualFileSnapshotCollection();

        foreach ($virtualFiles as $virtualFile) {
            $snapshots->add(VirtualFileSnapshot::fromVirtualFile($virtualFile));
        }

        return $snapshots;
    }
}
