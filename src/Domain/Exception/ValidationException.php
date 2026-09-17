<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

use function sprintf;

/**
 * Business rules rejected the input. Carries every violation at once, never the first one.
 */
final class ValidationException extends Exception
{
    /**
     * @param array<string, list<string>> $violations property path => error codes
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly array $violations = [],
    ) {
        parent::__construct(sprintf('Validation failed: %s', $errorCode));
    }
}
