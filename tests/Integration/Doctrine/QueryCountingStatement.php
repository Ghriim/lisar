<?php

declare(strict_types=1);

namespace App\Tests\Integration\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class QueryCountingStatement extends AbstractStatementMiddleware
{
    public function __construct(Statement $statement, private readonly QueryCounter $counter, private readonly string $sql)
    {
        parent::__construct($statement);
    }

    public function execute(): Result
    {
        $this->counter->record($this->sql);

        return parent::execute();
    }
}
