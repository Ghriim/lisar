<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Integration\Doctrine\QueryCounter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * How many statements a use case sends. Every use case has a budget test, against
 * DEFAULT_MAX_QUERIES unless it says why it needs more; every list has a test that its count does
 * not grow with what it lists.
 *
 * A measure starts from an empty entity manager — what the test loaded beforehand would hide the
 * lazy loads — so the caller and anything else the call takes is loaded by `$arrange`, uncounted,
 * the way the firewall loads the caller before the controller runs. `$act` gets what `$arrange`
 * returned and makes the one call measured.
 */
trait QueryCountTrait
{
    /** The ceiling a use case is held to unless its test raises it, saying why. */
    private const int DEFAULT_MAX_QUERIES = 5;

    /**
     * @template TArranged
     * @template TResult
     *
     * @param callable(): TArranged        $arrange
     * @param callable(TArranged): TResult $act
     *
     * @return TResult what the call answered
     */
    private function assertQueryCountAtMost(callable $arrange, callable $act, int $max = self::DEFAULT_MAX_QUERIES): mixed
    {
        [$queries, $result] = $this->recordQueries($arrange, $act);

        self::assertLessThanOrEqual($max, count($queries), sprintf("%d queries, %d at most:\n%s", count($queries), $max, implode("\n", $queries)));

        return $result;
    }

    /**
     * Measures the call, lets `$grow` add to what it lists, measures it again: the count must not
     * move. The test checks on the two answers that the list did grow.
     *
     * @template TArranged
     * @template TResult
     *
     * @param callable(): TArranged        $arrange
     * @param callable(TArranged): TResult $act
     * @param callable(): mixed            $grow
     *
     * @return array{TResult, TResult} the answers before and after growing
     */
    private function assertQueryCountDoesNotGrow(callable $arrange, callable $act, callable $grow): array
    {
        [$before, $fewer] = $this->recordQueries($arrange, $act);
        $grow();
        [$after, $more] = $this->recordQueries($arrange, $act);

        self::assertSame(count($before), count($after), sprintf(
            "%d queries before growing, %d after:\n%s\n--- after ---\n%s",
            count($before),
            count($after),
            implode("\n", $before),
            implode("\n", $after),
        ));

        return [$fewer, $more];
    }

    /**
     * @template TArranged
     * @template TResult
     *
     * @param callable(): TArranged        $arrange
     * @param callable(TArranged): TResult $act
     *
     * @return array{list<string>, TResult}
     */
    private function recordQueries(callable $arrange, callable $act): array
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $arranged = $arrange();

        $counter = self::getContainer()->get(QueryCounter::class);
        $counter->reset();
        $result = $act($arranged);

        return [$counter->queries, $result];
    }
}
