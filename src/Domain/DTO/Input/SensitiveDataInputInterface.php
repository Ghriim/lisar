<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input;

/**
 * Marker for a payload that must never reach the logs: credentials, tokens, personal data.
 *
 * Being logged is the default; opting out is this declaration, visible in review.
 */
interface SensitiveDataInputInterface extends DataInputInterface
{
}
