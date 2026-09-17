<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\DataModelFactory\TagDataModelFactory;
use App\Domain\Gateway\Persister\TagPersisterGateway;
use App\Domain\Gateway\Persister\TaskPersisterGateway;
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
        private readonly TagPersisterGateway $tagPersisterGateway,
        private readonly TagDataModelFactory $tagDataModelFactory,
    ) {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class, PriorityFixtures::class, CategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);
        $high = $this->getReference(PriorityFixtures::HIGH, PriorityDataModel::class);
        $low = $this->getReference(PriorityFixtures::LOW, PriorityDataModel::class);

        $urgent = $this->tagDataModelFactory->buildOne('urgent', $alice);
        $errand = $this->tagDataModelFactory->buildOne('errand', $alice);
        $this->tagPersisterGateway->createMany([$urgent, $errand]);

        $this->addReference(self::ALICE_PLAIN, $this->createTask(
            $alice,
            'Buy a birthday present',
            category: $home,
            priority: $high,
            dueDate: new DateTimeImmutable('2026-09-30'),
            tags: [$urgent, $errand],
        ));

        // A task with subtasks, one of them done: it reads as "in progress".
        $withSubtasks = $this->createTask($alice, 'Move the flat', category: $home, priority: $high);
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
            priority: $low,
        ));

        // And one already ticked off, for the completed view.
        $this->addReference(self::ALICE_DONE, $this->createTask(
            $alice,
            'Send the invoice',
            category: $work,
            completedAt: new DateTimeImmutable('2026-09-15 18:30:00'),
        ));
    }

    /**
     * @param TagDataModel[] $tags
     */
    private function createTask(
        UserDataModel $owner,
        string $title,
        ?CategoryDataModel $category = null,
        ?PriorityDataModel $priority = null,
        ?DateTimeImmutable $dueDate = null,
        ?TaskDataModel $parent = null,
        ?DateTimeImmutable $completedAt = null,
        array $tags = [],
    ): TaskDataModel {
        $task = new TaskDataModel();
        $task->owner = $owner;
        $task->title = $title;
        $task->category = $category;
        $task->priority = $priority;
        $task->dueDate = $dueDate;
        $task->parent = $parent;
        $task->completedAt = $completedAt;

        foreach ($tags as $tag) {
            $task->tags->add($tag);
        }

        return $this->taskPersisterGateway->create($task);
    }
}
