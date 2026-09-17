<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Exception;

/**
 * The payload could not be turned into a DataInput. The message is deliberately generic: the
 * details went to the logs, they never reach the caller.
 */
final class DataInputMappingException extends Exception
{
    public const string MESSAGE = 'The data provided seems to be invalid.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
