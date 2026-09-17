<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Domain\Validation\Constraint\Task\TaskClosableConstraint;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CompleteTaskUseCase;
use App\UseCase\Task\GetTaskUseCase;
use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use const DATE_ATOM;

final class CompleteTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CompleteTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CompleteTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItTicksATaskOff(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0);

        self::assertSame(TaskStateRegistry::DONE, $output->state);
        self::assertNotNull($output->completedAt);

        // Re-read through the gateway: assert it was really persisted.
        $reread = $this->taskProviderGateway->findOneByIdForOwner($task->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertTrue($reread->isDone());
    }

    public function testItRefusesAParentWhoseSubtaskIsStillOpen(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        try {
            $this->useCase->execute($this->aliceId(), $task->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CompleteTaskUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([TaskClosableConstraint::TASK_HAS_OPEN_SUBTASKS], $exception->violations['id']);
        }

        $reread = $this->taskProviderGateway->findOneByIdForOwner($task->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertFalse($reread->isDone());
    }

    public function testAParentBecomesClosableOnceEverySubtaskIsDone(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);
        $openSubtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);

        $this->useCase->execute($this->aliceId(), $openSubtask->id ?? 0);

        $readyToClose = self::getContainer()->get(GetTaskUseCase::class)
            ->execute($this->aliceId(), $parent->id ?? 0);
        self::assertSame(TaskStateRegistry::READY_TO_CLOSE, $readyToClose->state);

        self::assertSame(
            TaskStateRegistry::DONE,
            $this->useCase->execute($this->aliceId(), $parent->id ?? 0)->state,
        );
    }

    /**
     * Closing a parent never closes its subtasks: they were all done already, and nothing is
     * ticked off on the person's behalf.
     */
    public function testClosingAParentLeavesItsSubtasksAsTheyWere(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);
        $openSubtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);
        $doneSubtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class);

        $completedAtBefore = $doneSubtask->completedAt;

        $this->useCase->execute($this->aliceId(), $openSubtask->id ?? 0);
        $this->useCase->execute($this->aliceId(), $parent->id ?? 0);

        $reread = $this->taskProviderGateway->findOneByIdForOwner($doneSubtask->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertSame(
            $completedAtBefore?->format(DATE_ATOM),
            $reread->completedAt?->format(DATE_ATOM),
        );
    }

    public function testItIsIdempotent(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_DONE, TaskDataModel::class);
        $completedAtBefore = $task->completedAt;

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0);

        self::assertSame(TaskStateRegistry::DONE, $output->state);
        self::assertSame($completedAtBefore?->format(DATE_ATOM), $output->completedAt ? (new DateTimeImmutable($output->completedAt))->format(DATE_ATOM) : null);
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $task->id ?? 0);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
