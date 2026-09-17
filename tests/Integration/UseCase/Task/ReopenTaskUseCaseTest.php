<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\ListTasksDataInput;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\ListTasksUseCase;
use App\UseCase\Task\ReopenTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReopenTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ReopenTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ReopenTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItPutsADoneTaskBackOnTheList(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_DONE, TaskDataModel::class);

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0);

        self::assertSame(TaskStateRegistry::TO_DO, $output->state);
        self::assertNull($output->completedAt);

        $titles = array_map(
            static fn ($listed) => $listed->title,
            self::getContainer()->get(ListTasksUseCase::class)->execute($this->aliceId(), new ListTasksDataInput()),
        );
        self::assertContains('Send the invoice', $titles);
    }

    public function testReopeningASubtaskPullsItsParentBackToInProgress(): void
    {
        $doneSubtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class);
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        $this->useCase->execute($this->aliceId(), $doneSubtask->id ?? 0);

        // Both subtasks are open again, so the parent reads as "to do" rather than in progress.
        $reread = $this->taskProviderGateway->findOneByIdForOwner($parent->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertCount(2, $reread->getOpenSubtasks());
    }

    public function testItIsIdempotentOnAnOpenTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        self::assertSame(
            TaskStateRegistry::TO_DO,
            $this->useCase->execute($this->aliceId(), $task->id ?? 0)->state,
        );
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_DONE, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $task->id ?? 0);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
