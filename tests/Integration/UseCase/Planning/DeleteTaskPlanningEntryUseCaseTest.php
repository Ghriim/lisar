<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryDeletedEvent;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Planning\DeleteTaskPlanningEntryUseCase;
use App\UseCase\Todo\CompleteTaskUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a task entry: the tasks it made stay, unhooked from it. */
final class DeleteTaskPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RecordedEventsTrait;
    use TaskPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** The event carries the open tasks it had made, read before the delete; the tasks stay, without their entry. */
    public function testItDeletesAnEntry(): void
    {
        $entry = $this->withHandlers(fn () => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay()));
        $this->withHandlers(fn () => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->singleDate($this->inDays(3))));
        [$today] = $this->plannedTasks();
        $this->useCase(CompleteTaskUseCase::class)->execute($this->currentUser($this->aliceId), (int) $today->id);

        $this->forgetRaisedEvents();
        $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $entry->id);

        // Today's task is done: no open task left on that entry.
        self::assertSame([], $this->assertEventRaised(TaskPlanningEntryDeletedEvent::class)->openTasks);
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(self::getContainer()->get(TaskPlanningEntryProviderGateway::class)->findOneById($entry->id));
        $tasks = $this->plannedTasks();
        self::assertCount(2, $tasks);
        self::assertNull($tasks[0]->planningEntry);
        self::assertNotNull($tasks[1]->planningEntry);
    }

    public function testTheOpenTasksGoWithTheEvent(): void
    {
        $entry = $this->withHandlers(fn () => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->singleDate($this->inDays(3))));

        $this->forgetRaisedEvents();
        $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $entry->id);

        $openTasks = $this->assertEventRaised(TaskPlanningEntryDeletedEvent::class)->openTasks;
        self::assertSame([$this->inDays(3)], array_map(static fn ($task) => $task->plannedDate?->format('Y-m-d'), $openTasks));
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789);
    }

    public function testDeletingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($currentUser, $this->seededEntryId()),
            // The entry, its open tasks, then the DELETE, in its transaction.
            max: 5,
        );
    }
}
