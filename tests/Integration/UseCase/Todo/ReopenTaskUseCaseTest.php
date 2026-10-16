<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Registry\Todo\TaskStateRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\ListTasksUseCase;
use App\UseCase\Todo\ReopenTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReopenTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

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

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0);

        self::assertSame(TaskStateRegistry::TO_DO, $output->state);
        self::assertNull($output->completedAt);

        $titles = array_map(
            static fn ($listed) => $listed->title,
            self::getContainer()->get(ListTasksUseCase::class)->execute($this->currentUser($this->aliceId()), new ListTasksDataInput()),
        );
        self::assertContains('Send the invoice', $titles);
    }

    public function testReopeningASubtaskPullsItsParentBackToInProgress(): void
    {
        $doneSubtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_DONE, TaskDataModel::class);
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), $doneSubtask->id ?? 0);

        // Both subtasks are open again, so the parent reads as "to do" rather than in progress.
        $reread = $this->taskProviderGateway->findOneById($parent->id ?? 0);
        self::assertNotNull($reread);
        self::assertCount(2, $reread->getOpenSubtasks());
    }

    public function testItIsIdempotentOnAnOpenTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        self::assertSame(
            TaskStateRegistry::TO_DO,
            $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0)->state,
        );
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_DONE, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute($this->currentUser($admin->id ?? 0), $task->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testReopeningATaskStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $taskId = $this->getReference(TaskFixtures::ALICE_DONE, TaskDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $taskId),
        );
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
