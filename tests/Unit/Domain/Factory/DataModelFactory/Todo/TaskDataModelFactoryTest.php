<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\Factory\DataModelFactory\Todo\TaskDataModelFactory;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class TaskDataModelFactoryTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    /** The model's fields, the date as due date and planned date, the default priority, no time of day. */
    public function testAPlannedTaskIsMadeFromItsModel(): void
    {
        $entry = $this->taskEntry(7, $this->anyCurrentUser());
        $entry->description = 'Jaune le lundi.';
        $entry->category = new CategoryDataModel();
        $entry->startTimeInMinutes = 20 * 60;

        $task = (new TaskDataModelFactory())->buildPlanned($entry, new DateTimeImmutable('2026-11-30', new DateTimeZone('Europe/Paris')));

        self::assertSame($entry->owner, $task->owner);
        self::assertSame(['Sortir les poubelles', 'Jaune le lundi.', TaskPriorityRegistry::DEFAULT], [$task->title, $task->description, $task->priority]);
        self::assertSame($entry->category, $task->category);
        self::assertSame($entry, $task->planningEntry);
        self::assertSame(['2026-11-30 00:00:00', '2026-11-30'], [$task->dueDate?->format('Y-m-d H:i:s'), $task->plannedDate?->format('Y-m-d')]);
        self::assertNull($task->parent);
        self::assertNull($task->completedAt);
    }
}
