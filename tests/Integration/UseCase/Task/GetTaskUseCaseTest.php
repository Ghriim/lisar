<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\GetTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private GetTaskUseCase $useCase;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(GetTaskUseCase::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItReturnsTheTaskWithItsSubtasks(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0);

        self::assertSame('Move the flat', $output->title);
        self::assertSame(TaskStateRegistry::IN_PROGRESS, $output->state);
        self::assertCount(2, $output->subtasks);
    }

    public function testItReturnsASubtaskOnItsOwn(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class);

        $output = $this->useCase->execute($this->aliceId(), $subtask->id ?? 0);

        self::assertSame('Book the van', $output->title);
        self::assertSame(TaskStateRegistry::DONE, $output->state);
        self::assertNotNull($output->parentId);
    }

    public function testAnotherAccountsTaskIsSimplyNotFound(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute($admin->id ?? 0, $task->id ?? 0);
            self::fail('Expected DataModelNotFoundException');
        } catch (DataModelNotFoundException $exception) {
            self::assertSame(TaskDataModel::class, $exception->dataModelClass);
        }
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
