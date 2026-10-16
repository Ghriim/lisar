<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\OutputFactory\Todo\CategoryOutputFactory;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Registry\Todo\TaskStateRegistry;
use App\Domain\Todo\TaskState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TaskOutputFactoryTest extends TestCase
{
    private TaskOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $mapper = new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor());

        $this->factory = new TaskOutputFactory(
            $mapper,
            new TaskState(),
            new CategoryOutputFactory(),
        );
    }

    public function testItBuildsAPlainTask(): void
    {
        $task = $this->buildTask('Buy a present');
        $task->dueDate = new DateTimeImmutable('2026-09-30');
        $task->description = 'Something she would not buy herself.';

        $output = $this->factory->buildOne($task);

        self::assertSame(1, $output->id);
        self::assertSame('Buy a present', $output->title);
        self::assertSame('Something she would not buy herself.', $output->description);
        self::assertSame('2026-09-30', $output->dueDate);
        self::assertSame(TaskStateRegistry::TO_DO, $output->state);
        self::assertNull($output->parentId);
        self::assertSame([], $output->subtasks);
    }

    public function testItCarriesThePriorityAndNestsTheCategory(): void
    {
        $task = $this->buildTask('Buy a present');

        $task->priority = TaskPriorityRegistry::HIGH;

        $category = new CategoryDataModel();
        $category->id = 3;
        $category->label = 'Home';
        $category->owner = null;
        $task->category = $category;

        $output = $this->factory->buildOne($task);

        self::assertSame(TaskPriorityRegistry::HIGH, $output->priority);

        self::assertNotNull($output->category);
        self::assertSame('Home', $output->category->label);
        self::assertFalse($output->category->isPersonal);
    }

    public function testItMarksAPersonalCategoryAsSuch(): void
    {
        $task = $this->buildTask('Buy a present');

        $category = new CategoryDataModel();
        $category->id = 3;
        $category->label = 'Side project';
        $category->owner = $task->owner;
        $task->category = $category;

        $output = $this->factory->buildOne($task);

        self::assertNotNull($output->category);
        self::assertTrue($output->category->isPersonal);
    }

    public function testItNestsTheSubtasksAndDerivesTheState(): void
    {
        $task = $this->buildTask('Move the flat');

        $done = $this->buildTask('Book the van', id: 2);
        $done->parent = $task;
        $done->completedAt = new DateTimeImmutable('2026-09-16 09:00:00');
        $task->subtasks->add($done);

        $open = $this->buildTask('Pack the kitchen', id: 3);
        $open->parent = $task;
        $task->subtasks->add($open);

        $output = $this->factory->buildOne($task);

        self::assertSame(TaskStateRegistry::IN_PROGRESS, $output->state);
        self::assertCount(2, $output->subtasks);
        self::assertSame(TaskStateRegistry::DONE, $output->subtasks[0]->state);
        self::assertSame(1, $output->subtasks[0]->parentId);
        self::assertSame('2026-09-16T09:00:00+00:00', $output->subtasks[0]->completedAt);
        // One level only: a subtask never carries subtasks of its own.
        self::assertSame([], $output->subtasks[0]->subtasks);
    }

    public function testItBuildsManyOutputs(): void
    {
        $outputs = $this->factory->buildMany([
            $this->buildTask('First'),
            $this->buildTask('Second', id: 2),
        ]);

        self::assertCount(2, $outputs);
        self::assertSame('First', $outputs[0]->title);
        self::assertSame('Second', $outputs[1]->title);
    }

    private function buildTask(string $title, int $id = 1): TaskDataModel
    {
        $owner = new UserDataModel();
        $owner->id = 1;

        $task = new TaskDataModel();
        $task->id = $id;
        $task->title = $title;
        $task->owner = $owner;

        return $task;
    }
}
