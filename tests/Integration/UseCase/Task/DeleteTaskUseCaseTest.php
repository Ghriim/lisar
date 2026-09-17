<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\TagProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\DeleteTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private DeleteTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(DeleteTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItDeletesTheTaskForGood(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $id = $task->id ?? 0;

        $this->useCase->execute($this->aliceId(), $id);

        self::assertNull($this->taskProviderGateway->findOneByIdForOwner($id, $this->alice));
    }

    public function testItTakesTheSubtasksWithIt(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);
        $openSubtaskId = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class)->id ?? 0;
        $doneSubtaskId = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class)->id ?? 0;

        $this->useCase->execute($this->aliceId(), $parent->id ?? 0);

        self::assertNull($this->taskProviderGateway->findOneByIdForOwner($openSubtaskId, $this->alice));
        self::assertNull($this->taskProviderGateway->findOneByIdForOwner($doneSubtaskId, $this->alice));
    }

    public function testDeletingASubtaskLeavesItsParentAlone(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);

        $this->useCase->execute($this->aliceId(), $subtask->id ?? 0);

        $reread = $this->taskProviderGateway->findOneByIdForOwner($parent->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertCount(1, $reread->subtasks);
    }

    /**
     * The tags survive: they belong to the account, not to the task.
     */
    public function testItKeepsTheAccountsTags(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        $this->useCase->execute($this->aliceId(), $task->id ?? 0);

        self::assertCount(2, self::getContainer()->get(TagProviderGateway::class)->findAllForOwner($this->alice));
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $task->id ?? 0);
    }

    public function testItFailsOnAnUnknownTask(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->aliceId(), 123456789);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
