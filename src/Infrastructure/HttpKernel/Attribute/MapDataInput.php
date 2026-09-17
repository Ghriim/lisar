<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\Attribute;

use App\Infrastructure\HttpKernel\ArgumentResolver\DataInputValueResolver;
use Attribute;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;

/**
 * Builds a DataInput from the request, whatever the transport: query string and JSON body are
 * merged, so a controller signature does not change when a filter moves from one to the other.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class MapDataInput extends ValueResolver
{
    public function __construct(string $resolver = DataInputValueResolver::class)
    {
        parent::__construct($resolver);
    }
}
