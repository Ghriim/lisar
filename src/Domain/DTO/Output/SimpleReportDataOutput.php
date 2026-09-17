<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output;

/**
 * The generic {"message": "..."} envelope, for endpoints that only acknowledge.
 */
final class SimpleReportDataOutput
{
    public function __construct(public string $message)
    {
    }
}
