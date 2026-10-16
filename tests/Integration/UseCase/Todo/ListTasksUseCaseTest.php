<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Registry\Todo\TaskStateRegistry;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CreateTaskUseCase;
use App\UseCase\Todo\ListTasksUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListTasksUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListTasksUseCase $useCase;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListTasksUseCase::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItListsTheOpenRootTasksOnly(): void
    {
        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());

        $titles = array_map(static fn ($task) => $task->title, $tasks);

        // "Send the invoice" is done, and the two subtasks are nested rather than listed. Both
        // sit in "Home" with the same priority, so the newest comes first.
        self::assertSame(['Move the flat', 'Buy a birthday present'], $titles);
    }

    public function testItNestsTheSubtasksAndDerivesTheState(): void
    {
        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());

        $move = $tasks[0];
        self::assertSame('Move the flat', $move->title);
        self::assertSame(TaskStateRegistry::IN_PROGRESS, $move->state);
        self::assertCount(2, $move->subtasks);

        $states = array_map(static fn ($subtask) => $subtask->state, $move->subtasks);
        sort($states);
        self::assertSame([TaskStateRegistry::DONE, TaskStateRegistry::TO_DO], $states);
    }

    public function testItSortsByCategoryThenByPriority(): void
    {
        // Both fixtures sit in "Home"; a third one in "Work" must come after them.
        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());
        self::assertSame(['Home', 'Home'], array_map(static fn ($task) => $task->category?->label, $tasks));

        // A third task, in "Work", sorts after them.
        self::getContainer()->get(CreateTaskUseCase::class)->execute($this->currentUser($this->aliceId()), new CreateTaskDataInput(
            title: 'Water the plants',
            categoryId: $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class)->id,
        ));

        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());

        self::assertSame(
            ['Home', 'Home', 'Work'],
            array_map(static fn ($task) => $task->category?->label, $tasks),
        );
    }

    /**
     * Inside a category, most pressing first: the registry's order, not the codes' alphabetical
     * one, which would put « low » before « normal ».
     */
    public function testWithinACategoryThePriorityGoesFromHighToLow(): void
    {
        $homeId = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class)->id;
        foreach (['Later' => TaskPriorityRegistry::LOW, 'Sooner' => TaskPriorityRegistry::NORMAL] as $title => $priority) {
            self::getContainer()->get(CreateTaskUseCase::class)->execute(
                $this->currentUser($this->aliceId()),
                new CreateTaskDataInput($title, priority: $priority, categoryId: $homeId),
            );
        }

        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());

        // The two seeded ones are high.
        self::assertSame(
            ['Move the flat', 'Buy a birthday present', 'Sooner', 'Later'],
            array_map(static fn ($task) => $task->title, $tasks),
        );
    }

    public function testATaskWithoutACategorySortsLast(): void
    {
        self::getContainer()->get(CreateTaskUseCase::class)->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Nowhere in particular'),
        );

        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput());

        self::assertSame('Nowhere in particular', end($tasks)->title);
    }

    public function testTheCompletedViewHoldsTheDoneTasks(): void
    {
        $tasks = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTasksDataInput(isDone: true));

        self::assertSame(['Send the invoice'], array_map(static fn ($task) => $task->title, $tasks));
    }

    public function testItNeverLeaksAnotherAccountsTasks(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertSame([], $this->useCase->execute($this->currentUser($admin->id ?? 0), new ListTasksDataInput()));
    }

    public function testListingTheTasksStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new ListTasksDataInput()),
        );
    }

    /** Grown with tasks carrying what a task drags along: a category and subtasks. */
    public function testListingTheTasksDoesNotGrowWithThem(): void
    {
        $aliceId = $this->aliceId();
        $categoryId = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class)->id;
        $createTask = self::getContainer()->get(CreateTaskUseCase::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new ListTasksDataInput()),
            function () use ($aliceId, $categoryId, $createTask): void {
                foreach (['Ship the landing page', 'Write the docs', 'Book the venue'] as $title) {
                    $parent = $createTask->execute($this->currentUser($aliceId), new CreateTaskDataInput(
                        title: $title,
                        categoryId: $categoryId,
                    ));
                    $createTask->execute($this->currentUser($aliceId), new CreateTaskDataInput(title: $title.', step one', parentId: $parent->id));
                    $createTask->execute($this->currentUser($aliceId), new CreateTaskDataInput(title: $title.', step two', parentId: $parent->id));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
