<?php

declare(strict_types=1);

namespace App\Fixtures\Todo;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Fixtures\Category\CategoryFixtures;
use App\Fixtures\User\UserFixtures;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * One task per shape the list can be in, so that a front end has every state to render: in
 * Alice's « A faire », a late one and a done one; in « Maison », a favourite with subtasks; in
 * « Vieux projets », archived, one that shows nowhere.
 */
final class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PLAIN = 'task-alice-plain';
    public const string ALICE_WITH_SUBTASKS = 'task-alice-with-subtasks';
    public const string ALICE_SUBTASK_DONE = 'task-alice-subtask-done';
    public const string ALICE_SUBTASK_OPEN = 'task-alice-subtask-open';
    public const string ALICE_DONE = 'task-alice-done';
    public const string ALICE_ARCHIVED = 'task-alice-archived';

    public function __construct(
        private readonly TaskPersisterGateway $taskPersisterGateway,
    ) {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class, CategoryFixtures::class, TodoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);
        $default = $this->getReference(TodoFixtures::ALICE_DEFAULT, TodoDataModel::class);
        $todoHome = $this->getReference(TodoFixtures::ALICE_HOME, TodoDataModel::class);

        $this->addReference(self::ALICE_PLAIN, $this->createTask(
            $alice,
            $default,
            'Buy a birthday present',
            category: $home,
            priority: TaskPriorityRegistry::HIGH,
            dueDate: new DateTimeImmutable('2026-09-30'),
        ));

        // A task with subtasks, one of them done: it reads as "in progress".
        // Starred, with no date: the dashboard shows it for its star alone.
        $withSubtasks = $this->createTask($alice, $todoHome, 'Move the flat', category: $home, priority: TaskPriorityRegistry::HIGH, isFavorite: true);
        $this->addReference(self::ALICE_WITH_SUBTASKS, $withSubtasks);
        $this->addReference(self::ALICE_SUBTASK_DONE, $this->createTask(
            $alice,
            $todoHome,
            'Book the van',
            parent: $withSubtasks,
            completedAt: new DateTimeImmutable('2026-09-16 09:00:00'),
        ));
        $this->addReference(self::ALICE_SUBTASK_OPEN, $this->createTask(
            $alice,
            $todoHome,
            'Pack the kitchen',
            parent: $withSubtasks,
            priority: TaskPriorityRegistry::LOW,
        ));

        // And one already ticked off, for the completed view.
        $this->addReference(self::ALICE_DONE, $this->createTask(
            $alice,
            $default,
            'Send the invoice',
            category: $work,
            completedAt: new DateTimeImmutable('2026-09-15 18:30:00'),
        ));

        // Late and starred, but in an archived TODO: nowhere to be seen.
        $this->addReference(self::ALICE_ARCHIVED, $this->createTask(
            $alice,
            $this->getReference(TodoFixtures::ALICE_ARCHIVED, TodoDataModel::class),
            'Learn the banjo',
            dueDate: new DateTimeImmutable('2026-01-10'),
            isFavorite: true,
        ));
    }

    private function createTask(
        UserDataModel $owner,
        TodoDataModel $todo,
        string $title,
        ?CategoryDataModel $category = null,
        ?string $priority = null,
        ?DateTimeImmutable $dueDate = null,
        ?TaskDataModel $parent = null,
        ?DateTimeImmutable $completedAt = null,
        bool $isFavorite = false,
    ): TaskDataModel {
        $task = new TaskDataModel();
        $task->owner = $owner;
        $task->todo = $todo;
        $task->isFavorite = $isFavorite;
        $task->title = $title;
        $task->category = $category;
        $task->priority = $priority;
        $task->dueDate = $dueDate;
        $task->parent = $parent;
        $task->completedAt = $completedAt;

        return $this->taskPersisterGateway->create($task);
    }
}
