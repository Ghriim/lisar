<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;

/** An account was made — signed up, or created from the back-office. */
final readonly class UserCreatedEvent implements EventInterface
{
    public function __construct(
        public UserDataModel $user,
    ) {
    }
}
