<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\Attribute;

use Attribute;

/**
 * Marks a DataInput field whose surrounding whitespace is meaningful, and which must therefore
 * reach the application exactly as it was sent — opting it out of the trimming the resolver
 * applies to everything else (§6.12).
 *
 * The case it exists for is a secret: trimming a password silently forbids the ones that begin
 * or end with a space, and does it without telling anyone.
 *
 * It lives next to the resolver that reads it, and is one of the two vendor-like symbols a
 * DataInput is allowed to import from outside the Domain — see §2.1.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class NotTrimmed
{
}
