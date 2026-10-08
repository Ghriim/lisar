<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EventHandler;

use App\Domain\DTO\Event\EventInterface;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use App\Infrastructure\EventHandler\SynchronousEventDispatcher;
use PHPUnit\Framework\TestCase;

final class SynchronousEventDispatcherTest extends TestCase
{
    public function testEveryHandlerOfAnEventRunsAndNoOther(): void
    {
        $first = new FirstEventHandler();
        $both = new BothEventsHandler();
        $second = new SecondEventHandler();

        $event = new FirstEvent();
        (new SynchronousEventDispatcher([$first, $both, $second]))->dispatch($event);

        self::assertSame([$event], $first->handled);
        self::assertSame([$event], $both->handled);
        self::assertSame([], $second->handled);
    }

    public function testAnEventNobodyListensToIsFine(): void
    {
        (new SynchronousEventDispatcher([new SecondEventHandler()]))->dispatch(new FirstEvent());

        $this->expectNotToPerformAssertions();
    }
}

final class FirstEvent implements EventInterface
{
}

final class SecondEvent implements EventInterface
{
}

abstract class RecordingEventHandler implements EventHandlerInterface
{
    /** @var list<EventInterface> */
    public array $handled = [];

    public function handle(EventInterface $event): void
    {
        $this->handled[] = $event;
    }
}

final class FirstEventHandler extends RecordingEventHandler
{
    public static function getSupportedEvents(): array
    {
        return [FirstEvent::class];
    }
}

final class SecondEventHandler extends RecordingEventHandler
{
    public static function getSupportedEvents(): array
    {
        return [SecondEvent::class];
    }
}

final class BothEventsHandler extends RecordingEventHandler
{
    public static function getSupportedEvents(): array
    {
        return [FirstEvent::class, SecondEvent::class];
    }
}
