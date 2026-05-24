<?php

declare(strict_types=1);

namespace BabelForge\PhpRefactor\Domain\Journal;

/**
 * Journal of global refactor transaction actions.
 *
 * @implements \IteratorAggregate<RefactorActionJournalEntry>
 */
final class RefactorActionJournal implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RefactorActionJournalEntry>
     */
    private array $entries = [];

    /**
     * Creates an empty action journal.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Adds an entry.
     *
     * @param RefactorActionJournalEntry $entry the journal entry to add
     */
    public function add(RefactorActionJournalEntry $entry): self
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Returns the journal iterator.
     *
     * @return \Traversable<RefactorActionJournalEntry>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }

    /**
     * Counts journal entries.
     */
    public function count(): int
    {
        return count($this->entries);
    }
}
