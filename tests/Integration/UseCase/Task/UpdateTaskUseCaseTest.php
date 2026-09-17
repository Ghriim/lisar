<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\TagProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\UpdateTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private UpdateTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private TagProviderGateway $tagProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(UpdateTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);
        $this->tagProviderGateway = self::getContainer()->get(TagProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItUpdatesTheTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0, new UpdateTaskDataInput(
            title: 'Buy a better present',
            description: 'A book, maybe.',
            dueDate: '2026-10-15',
            categoryId: $work->id,
            tags: ['gift'],
        ));

        self::assertSame('Buy a better present', $output->title);
        self::assertSame('A book, maybe.', $output->description);
        self::assertSame('2026-10-15', $output->dueDate);
        self::assertSame('Work', $output->category?->label);
        self::assertSame(['gift'], $output->tags);

        // Re-read through the gateway: assert it was really persisted.
        $reread = $this->taskProviderGateway->findOneByIdForOwner($task->id ?? 0, $this->alice);
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

        $output = $this->useCase->execute($this->aliceId(), $task->id ?? 0, new UpdateTaskDataInput('Buy a present'));

        self::assertNull($output->description);
        self::assertNull($output->dueDate);
        self::assertNull($output->priority);
        self::assertNull($output->category);
        self::assertSame([], $output->tags);
    }

    public function testItKeepsTheAccountsTagAfterRemovingItFromTheTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        $this->useCase->execute($this->aliceId(), $task->id ?? 0, new UpdateTaskDataInput('Buy a present'));

        // The task no longer carries them, but the account keeps them to be offered again.
        $labels = [];
        foreach ($this->tagProviderGateway->findAllForOwner($this->alice) as $tag) {
            $labels[] = $tag->label;
        }
        self::assertSame(['errand', 'urgent'], $labels);
    }

    public function testItUpdatesASubtaskWithoutTouchingItsParent(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);
        $parentId = $subtask->parent?->id;

        $output = $this->useCase->execute($this->aliceId(), $subtask->id ?? 0, new UpdateTaskDataInput('Pack it all'));

        self::assertSame('Pack it all', $output->title);
        self::assertSame($parentId, $output->parentId);
    }

    public function testItRefusesAnotherAccountsTask(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $task->id ?? 0, new UpdateTaskDataInput('Mine now'));
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        $task = $this->getReference(TaskFixtures::ALICE_PLAIN, TaskDataModel::class);

        try {
            $this->useCase->execute($this->aliceId(), $task->id ?? 0, new UpdateTaskDataInput('', priorityId: 404));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('priorityId', $exception->violations);
        }
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
