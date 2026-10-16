<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\UpdateTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UpdateTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(UpdateTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItUpdatesTheTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0, new UpdateTaskDataInput(
            title: 'Buy a better present',
            description: 'A book, maybe.',
            dueDate: '2026-10-15',
            categoryId: $work->id,
        ));

        self::assertSame('Buy a better present', $output->title);
        self::assertSame('A book, maybe.', $output->description);
        self::assertSame('2026-10-15', $output->dueDate);
        self::assertSame('Work', $output->category?->label);

        // Re-read through the gateway: assert it was really persisted.
        $reread = $this->taskProviderGateway->findOneById($task->id ?? 0);
        self::assertNotNull($reread);
        self::assertSame('Buy a better present', $reread->title);
        self::assertSame('Work', $reread->category?->label);
    }

    /**
     * An update is a full replacement: what the caller leaves out is cleared, the default
     * priority is not re-applied behind their back.
     */
    public function testItClearsWhatTheCallerLeavesOut(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0, new UpdateTaskDataInput('Buy a present'));

        self::assertNull($output->description);
        self::assertNull($output->dueDate);
        self::assertNull($output->priority);
        self::assertNull($output->category);
    }

    public function testItUpdatesASubtaskWithoutTouchingItsParent(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);
        $parentId = $subtask->parent?->id;

        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $subtask->id ?? 0, new UpdateTaskDataInput('Pack it all'));

        self::assertSame('Pack it all', $output->title);
        self::assertSame($parentId, $output->parentId);
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute($this->currentUser($admin->id ?? 0), $task->id ?? 0, new UpdateTaskDataInput('Mine now'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        try {
            $this->useCase->execute($this->currentUser($this->aliceId()), $task->id ?? 0, new UpdateTaskDataInput('', priority: 'urgent'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('priority', $exception->violations);
        }
    }

    public function testUpdatingATaskStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $taskId = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class)->id ?? 0;
        $categoryId = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $taskId, new UpdateTaskDataInput(
                title: 'Buy a present',
                priority: TaskPriorityRegistry::LOW,
                categoryId: $categoryId,
            )),
        );
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
