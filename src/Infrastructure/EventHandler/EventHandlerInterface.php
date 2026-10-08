<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler;

use App\Domain\DTO\Event\EventInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Reacts to events raised by a write. Implementing the interface is the whole registration: the
 * tag lands on every implementation, and the dispatcher finds it by the events it declares.
 */
#[AutoconfigureTag(self::TAG)]
interface EventHandlerInterface
{
    public const string TAG = 'app.event_handler';

    /**
     * The event classes this handler listens to.
     *
     * @return list<class-string<EventInterface>>
     */
    public static function getSupportedEvents(): array;

    public function handle(EventInterface $event): void;
}
