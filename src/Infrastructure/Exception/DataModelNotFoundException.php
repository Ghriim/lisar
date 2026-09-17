<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use App\Domain\DTO\DataModel\DataModelInterface;
use Exception;

use function sprintf;

/**
 * Always constructed with the data model class, never with free text: the class name is what
 * makes the 404 bodies uniform and greppable.
 */
final class DataModelNotFoundException extends Exception
{
    /**
     * @param class-string<DataModelInterface> $dataModelClass
     */
    public function __construct(public readonly string $dataModelClass)
    {
        parent::__construct(sprintf('DataModel %s not found', $dataModelClass));
    }
}
