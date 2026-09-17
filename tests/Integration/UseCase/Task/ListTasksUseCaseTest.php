<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\DTO\Input\Task\ListTasksDataInput;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CreateTaskUseCase;
use App\UseCase\Task\ListTasksUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListTasksUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

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
        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());

        $titles = array_map(static fn ($task) => $task->title, $tasks);

        // "Send the invoice" is done, and the two subtasks are nested rather than listed. Both
        // sit in "Home" with the same priority, so the newest comes first.
        self::assertSame(['Move the flat', 'Buy a birthday present'], $titles);
    }

    public function testItNestsTheSubtasksAndDerivesTheState(): void
    {
        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());

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
        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());
        self::assertSame(['Home', 'Home'], array_map(static fn ($task) => $task->category?->label, $tasks));

        // Within "Home", "High" (weight 10) comes before "High" — same weight, so add a "Low" one.
        self::getContainer()->get(CreateTaskUseCase::class)->execute($this->aliceId(), new CreateTaskDataInput(
            title: 'Water the plants',
            categoryId: $this->getReference(CategoryFixtures::WORK, \App\Domain\DTO\DataModel\CategoryDataModel::class)->id,
        ));

        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());

        self::assertSame(
            ['Home', 'Home', 'Work'],
            array_map(static fn ($task) => $task->category?->label, $tasks),
        );
    }

    public function testATaskWithoutACategorySortsLast(): void
    {
        self::getContainer()->get(CreateTaskUseCase::class)->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Nowhere in particular'),
        );

        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());

        self::assertSame('Nowhere in particular', end($tasks)->title);
    }

    public function testTheCompletedViewHoldsTheDoneTasks(): void
    {
        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput(isDone: true));

        self::assertSame(['Send the invoice'], array_map(static fn ($task) => $task->title, $tasks));
    }

    public function testItNeverLeaksAnotherAccountsTasks(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertSame([], $this->useCase->execute($admin->id ?? 0, new ListTasksDataInput()));
    }

    public function testItCarriesTheTags(): void
    {
        $tasks = $this->useCase->execute($this->aliceId(), new ListTasksDataInput());

        self::assertSame(['errand', 'urgent'], $tasks[1]->tags);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
