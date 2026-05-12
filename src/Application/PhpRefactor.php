<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRefactor\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRefactor\Application\Adapter\RenameServiceAdapter;
use PhpNoobs\PhpRefactor\Application\Adapter\RetypeServiceAdapter;
use PhpNoobs\PhpRefactor\Application\Snapshot\VirtualFileSnapshotter;
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRetype\Application\PhpRetype;

/**
 * Public facade for composed PHP refactoring workflows.
 */
final readonly class PhpRefactor
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild $build                the initial semantic build
     * @param RenameServiceAdapter       $renameServiceAdapter the rename service adapter
     * @param RetypeServiceAdapter       $retypeServiceAdapter the retype service adapter
     * @param VirtualFileSnapshotter     $snapshotter          the global virtual file snapshotter
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private RenameServiceAdapter $renameServiceAdapter,
        private RetypeServiceAdapter $retypeServiceAdapter,
        private VirtualFileSnapshotter $snapshotter,
    ) {
    }

    /**
     * Creates a refactor facade from project directories.
     *
     * @param list<string> $directories         the directories to scan
     * @param string       $cacheFilePath       the member graph cache file path
     * @param list<string> $excludedDirectories the directories to exclude from scanning
     * @param bool         $clearCache          whether the member graph cache must be cleared first
     */
    public static function fromDirectory(
        array $directories,
        string $cacheFilePath,
        array $excludedDirectories = [],
        bool $clearCache = false,
    ): self {
        return self::fromBuild(MemberDependencyGraphFactory::fromDirectory(
            directories: $directories,
            cacheFilePath: $cacheFilePath,
            excludedDirectories: $excludedDirectories,
            clearCache: $clearCache,
        ));
    }

    /**
     * Creates a refactor facade from an existing member graph build.
     *
     * @param MemberDependencyGraphBuild $build                the existing member graph build
     * @param RenameServiceAdapter|null  $renameServiceAdapter the optional rename adapter override
     * @param RetypeServiceAdapter|null  $retypeServiceAdapter the optional retype adapter override
     * @param VirtualFileSnapshotter     $snapshotter          the global virtual file snapshotter
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?RenameServiceAdapter $renameServiceAdapter = null,
        ?RetypeServiceAdapter $retypeServiceAdapter = null,
        VirtualFileSnapshotter $snapshotter = new VirtualFileSnapshotter(),
    ): self {
        return new self(
            build: $build,
            renameServiceAdapter: $renameServiceAdapter ?? new RenameServiceAdapter(PhpRename::fromBuild($build)),
            retypeServiceAdapter: $retypeServiceAdapter ?? new RetypeServiceAdapter(PhpRetype::fromBuild($build)),
            snapshotter: $snapshotter,
        );
    }

    /**
     * Starts a global refactor transaction.
     */
    public function beginTransaction(): PhpRefactorTransaction
    {
        return PhpRefactorTransaction::begin(
            build: $this->build,
            renameServiceAdapter: $this->renameServiceAdapter,
            retypeServiceAdapter: $this->retypeServiceAdapter,
            snapshotter: $this->snapshotter,
        );
    }
}
