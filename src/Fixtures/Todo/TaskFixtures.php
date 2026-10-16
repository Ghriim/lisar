<?php

declare(strict_types=1);

namespace App\Fixtures\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Fixtures\User\UserFixtures;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * One task per shape the list can be in, so that a front end has every state to render.
 */
final class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PLAIN = 'task-alice-plain';
    public const string ALICE_WITH_SUBTASKS = 'task-alice-with-subtasks';
    public const string ALICE_SUBTASK_DONE = 'task-alice-subtask-done';
    public const string ALICE_SUBTASK_OPEN = 'task-alice-subtask-open';
    public const string ALICE_DONE = 'task-alice-done';

    public function __construct(
        private readonly TaskPersisterGateway $taskPersisterGateway,
    ) {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class, CategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);

        $this->addReference(self::ALICE_PLAIN, $this->createTask(
            $alice,
            'Buy a birthday present',
            category: $home,
            priority: TaskPriorityRegistry::HIGH,
            dueDate: new DateTimeImmutable('2026-09-30'),
        ));

        // A task with subtasks, one of them done: it reads as "in progress".
        $withSubtasks = $this->createTask($alice, 'Move the flat', category: $home, priority: TaskPriorityRegistry::HIGH);
        $this->addReference(self::ALICE_WITH_SUBTASKS, $withSubtasks);
        $this->addReference(self::ALICE_SUBTASK_DONE, $this->createTask(
            $alice,
            'Book the van',
            parent: $withSubtasks,
            completedAt: new DateTimeImmutable('2026-09-16 09:00:00'),
        ));
        $this->addReference(self::ALICE_SUBTASK_OPEN, $this->createTask(
            $alice,
            'Pack the kitchen',
            parent: $withSubtasks,
            priority: TaskPriorityRegistry::LOW,
        ));

        // And one already ticked off, for the completed view.
        $this->addReference(self::ALICE_DONE, $this->createTask(
            $alice,
            'Send the invoice',
            category: $work,
            completedAt: new DateTimeImmutable('2026-09-15 18:30:00'),
        ));
    }

    private function createTask(
        UserDataModel $owner,
        string $title,
        ?CategoryDataModel $category = null,
        ?string $priority = null,
        ?DateTimeImmutable $dueDate = null,
        ?TaskDataModel $parent = null,
        ?DateTimeImmutable $completedAt = null,
    ): TaskDataModel {
        $task = new TaskDataModel();
        $task->owner = $owner;
        $task->title = $title;
        $task->category = $category;
        $task->priority = $priority;
        $task->dueDate = $dueDate;
        $task->parent = $parent;
        $task->completedAt = $completedAt;

        return $this->taskPersisterGateway->create($task);
    }
}
