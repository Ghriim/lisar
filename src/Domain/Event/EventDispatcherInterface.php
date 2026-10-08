<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\DTO\Event\EventInterface;

/**
 * Hands an event to every handler listening to it, synchronously: when `dispatch` returns, they
 * have all run. One that throws stops the others, and the exception reaches the caller.
 */
interface EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void;
}
