<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event;

/**
 * Something a write did, said once it is done. A persister raises it after its flush; the
 * handlers that listen to it run synchronously, in the same transaction as the write.
 *
 * An event carries what its handlers need, read before the write when the write destroys it — a
 * deleted set no longer knows its owner.
 */
interface EventInterface
{
}
