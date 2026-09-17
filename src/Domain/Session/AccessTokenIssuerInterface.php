<?php

declare(strict_types=1);

namespace App\Domain\Session;

use App\Domain\DTO\DataModel\UserDataModel;

/**
 * Mints the short-lived token the API is called with. Signing is an infrastructure concern and
 * lives there; this is the seam that keeps it out of the use cases.
 */
interface AccessTokenIssuerInterface
{
    public function issue(UserDataModel $user): string;

    /** How long the tokens it issues stay valid, in seconds. */
    public function getTtlInSeconds(): int;
}
