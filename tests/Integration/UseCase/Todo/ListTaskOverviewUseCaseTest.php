<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\Todo\TodoFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CompleteTaskUseCase;
use App\UseCase\Todo\CreateTaskUseCase;
use App\UseCase\Todo\FavoriteTaskUseCase;
use App\UseCase\Todo\ListTaskOverviewUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Alice's seed: « Buy a birthday present », late, in « A faire »; « Move the flat », starred, no
 * date, in « Maison »; « Send the invoice », done long ago; « Learn the banjo », late and starred,
 * but in « Vieux projets », archived.
 */
final class ListTaskOverviewUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private ListTaskOverviewUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListTaskOverviewUseCase::class);
        $this->loadFixtures(TaskFixtures::class);
    }

    /** The late one and the starred one, across TODOs; neither the done one nor the archived TODO's. */
    public function testItListsTheLateAndTheStarredAcrossTheActiveTodos(): void
    {
        self::assertEqualsCanonicalizing(['Buy a birthday present', 'Move the flat'], $this->titles());
    }

    public function testItNestsTheSubtasksAndSaysEachTasksTodo(): void
    {
        $byTitle = [];
        foreach ($this->useCase->execute($this->currentUser($this->aliceId())) as $task) {
            $byTitle[$task->title] = $task;
        }

        self::assertCount(2, $byTitle['Move the flat']->subtasks);
        self::assertSame($this->todoId(TodoFixtures::ALICE_HOME), $byTitle['Move the flat']->todoId);
        self::assertSame($this->todoId(TodoFixtures::ALICE_DEFAULT), $byTitle['Buy a birthday present']->todoId);
    }

    /** Today's, open or done today, shows; tomorrow's only with a star; a star done today still does. */
    public function testWhatWasDoneTodayStaysInSight(): void
    {
        $today = $this->day('today');
        $dueToday = $this->create('Pay the rent', $today);
        $this->create('Call the plumber', $today);
        $this->create('Plan the trip', $this->day('+1 day'));
        $starredTomorrow = $this->create('Book the train', $this->day('+1 day'));
        $doneStar = $this->create('Read the lease', null);

        $this->favorite($starredTomorrow);
        $this->favorite($doneStar);
        $this->complete($dueToday);
        $this->complete($doneStar);

        self::assertEqualsCanonicalizing(
            ['Buy a birthday present', 'Move the flat', 'Pay the rent', 'Call the plumber', 'Book the train', 'Read the lease'],
            $this->titles(),
        );
    }

    /** Done today, but due long ago and done — not today's: it does not come back. */
    public function testALateTaskDoneTodayStaysAsTodaysDone(): void
    {
        $this->complete($this->taskId(TaskFixtures::ALICE_PLAIN));

        $plain = array_values(array_filter(
            $this->useCase->execute($this->currentUser($this->aliceId())),
            static fn (TaskDataOutput $task) => 'Buy a birthday present' === $task->title,
        ));
        self::assertCount(1, $plain);
        self::assertNotNull($plain[0]->completedAt);
    }

    public function testSomeoneElsesTasksNeverShow(): void
    {
        self::assertSame([], $this->useCase->execute($this->currentUser($this->adminId())));
    }

    public function testListingStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser),
        );
    }

    public function testTheQueryCountDoesNotGrowWithTheTasks(): void
    {
        $aliceId = $this->aliceId();

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser),
            function (): void {
                foreach (['One', 'Two', 'Three'] as $title) {
                    $this->create($title, $this->day('today'));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }

    /** @return list<string> */
    private function titles(): array
    {
        return array_map(static fn (TaskDataOutput $task) => $task->title, $this->useCase->execute($this->currentUser($this->aliceId())));
    }

    private function day(string $modifier): string
    {
        return self::getContainer()->get(DayClock::class)->today()->modify($modifier)->format('Y-m-d');
    }

    private function create(string $title, ?string $dueDate): int
    {
        return self::getContainer()->get(CreateTaskUseCase::class)->execute($this->currentUser($this->aliceId()), new CreateTaskDataInput($title, dueDate: $dueDate))->id;
    }

    private function favorite(int $taskId): void
    {
        self::getContainer()->get(FavoriteTaskUseCase::class)->execute($this->currentUser($this->aliceId()), $taskId);
    }

    private function complete(int $taskId): void
    {
        self::getContainer()->get(CompleteTaskUseCase::class)->execute($this->currentUser($this->aliceId()), $taskId);
    }
}
