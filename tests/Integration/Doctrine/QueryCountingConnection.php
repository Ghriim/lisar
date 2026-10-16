<?php

declare(strict_types=1);

namespace App\Tests\Integration\Doctrine;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/** A prepared statement counts when it runs, once per run; a direct query or exec counts at once. */
final class QueryCountingConnection extends AbstractConnectionMiddleware
{
    public function __construct(Connection $connection, private readonly QueryCounter $counter)
    {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new QueryCountingStatement(parent::prepare($sql), $this->counter, $sql);
    }

    public function query(string $sql): Result
    {
        $this->counter->record($sql);

        return parent::query($sql);
    }

    public function exec(string $sql): int|string
    {
        $this->counter->record($sql);

        return parent::exec($sql);
    }
}
