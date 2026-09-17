<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Factory\OutputFactory\PriorityOutputFactory;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Domain\Task\TaskState;
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
            new PriorityOutputFactory($mapper),
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

    public function testItNestsThePriorityAndTheCategory(): void
    {
        $task = $this->buildTask('Buy a present');

        $priority = new PriorityDataModel();
        $priority->id = 7;
        $priority->label = 'High';
        $priority->colour = '#e5484d';
        $priority->weight = 10;
        $priority->isDefault = false;
        $task->priority = $priority;

        $category = new CategoryDataModel();
        $category->id = 3;
        $category->label = 'Home';
        $category->owner = null;
        $task->category = $category;

        $output = $this->factory->buildOne($task);

        self::assertNotNull($output->priority);
        self::assertSame('High', $output->priority->label);
        self::assertSame(10, $output->priority->weight);

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

    public function testItReturnsTagsAsSortedLabels(): void
    {
        $task = $this->buildTask('Buy a present');
        $task->tags->add($this->buildTag('urgent', $task->owner));
        $task->tags->add($this->buildTag('errand', $task->owner));

        self::assertSame(['errand', 'urgent'], $this->factory->buildOne($task)->tags);
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

    private function buildTag(string $label, UserDataModel $owner): TagDataModel
    {
        $tag = new TagDataModel();
        $tag->label = $label;
        $tag->owner = $owner;

        return $tag;
    }
}
