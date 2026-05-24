<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Diagnostic;

/**
 * Collection of refactor diagnostics.
 *
 * @implements \IteratorAggregate<RefactorDiagnostic>
 */
final class RefactorDiagnosticCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RefactorDiagnostic>
     */
    private array $diagnostics = [];

    /**
     * Creates an empty diagnostic collection.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Adds a diagnostic.
     *
     * @param RefactorDiagnostic $diagnostic the diagnostic to add
     */
    public function add(RefactorDiagnostic $diagnostic): self
    {
        $this->diagnostics[] = $diagnostic;

        return $this;
    }

    /**
     * Merges another collection into this collection.
     *
     * @param self $diagnostics the diagnostics to merge
     */
    public function merge(self $diagnostics): self
    {
        foreach ($diagnostics as $diagnostic) {
            $this->add($diagnostic);
        }

        return $this;
    }

    /**
     * Indicates whether the collection contains at least one error diagnostic.
     */
    public function hasErrors(): bool
    {
        foreach ($this->diagnostics as $diagnostic) {
            if (RefactorDiagnosticSeverity::ERROR === $diagnostic->severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the collection iterator.
     *
     * @return \Traversable<RefactorDiagnostic>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->diagnostics;
    }

    /**
     * Counts diagnostics.
     */
    public function count(): int
    {
        return count($this->diagnostics);
    }
}
