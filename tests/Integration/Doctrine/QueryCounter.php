<?php

declare(strict_types=1);

namespace App\Tests\Integration\Doctrine;

/**
 * Every statement sent to the database since the last reset, in the order sent. Savepoints are
 * left out: the test bundle wraps each test in a transaction, so every flush opens one, and that
 * is the test's doing, not the use case's.
 */
final class QueryCounter
{
    /** @var list<string> */
    public private(set) array $queries = [];

    public function reset(): void
    {
        $this->queries = [];
    }

    public function record(string $sql): void
    {
        if (1 === preg_match('/^\s*(SAVEPOINT|RELEASE SAVEPOINT|ROLLBACK TO SAVEPOINT)\b/i', $sql)) {
            return;
        }

        $this->queries[] = $sql;
    }
}
