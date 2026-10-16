<?php

declare(strict_types=1);

namespace App\Tests\Integration\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/** Hands every statement the connection sends to the counter. Wired in the test environment only. */
final readonly class QueryCountingMiddleware implements Middleware
{
    public function __construct(private QueryCounter $counter)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new QueryCountingDriver($driver, $this->counter);
    }
}
