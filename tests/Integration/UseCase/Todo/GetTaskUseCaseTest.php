<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TaskStateRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\GetTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

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

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0);

        self::assertSame('Move the flat', $output->title);
        self::assertSame(TaskStateRegistry::IN_PROGRESS, $output->state);
        self::assertCount(2, $output->subtasks);
    }

    public function testItReturnsASubtaskOnItsOwn(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class);

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $subtask->id ?? 0);

        self::assertSame('Book the van', $output->title);
        self::assertSame(TaskStateRegistry::DONE, $output->state);
        self::assertNotNull($output->parentId);
    }

    public function testAnotherAccountsTaskIsRefused(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute($this->currentUser($admin->id ?? 0), $task->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItFailsOnAnUnknownTask(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), 123456789);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
