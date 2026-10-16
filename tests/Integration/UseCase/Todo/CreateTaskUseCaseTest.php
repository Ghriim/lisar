<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Registry\Todo\TaskStateRegistry;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Todo\ParentTaskUsableConstraint;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CreateTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItCreatesATask(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), new CreateTaskDataInput(
            title: 'Call the dentist',
            description: 'Ask for an evening slot.',
            dueDate: '2026-10-02',
        ));

        self::assertSame('Call the dentist', $output->title);
        self::assertSame('Ask for an evening slot.', $output->description);
        self::assertSame('2026-10-02', $output->dueDate);
        self::assertSame(TaskStateRegistry::TO_DO, $output->state);
        self::assertNull($output->parentId);

        // Re-read through the gateway: assert it was really persisted.
        $task = $this->taskProviderGateway->findOneById($output->id);
        self::assertNotNull($task);
        self::assertSame('Call the dentist', $task->title);
        self::assertSame('2026-10-02', $task->dueDate?->format('Y-m-d'));
        self::assertFalse($task->isDone());
    }

    /**
     * The default applies when the caller names no priority.
     */
    public function testItAppliesTheDefaultPriority(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), new CreateTaskDataInput('Call the dentist'));

        self::assertSame(TaskPriorityRegistry::NORMAL, $output->priority);
    }

    public function testItKeepsThePriorityTheCallerAsksFor(): void
    {
        $output = $this->useCase->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Call the dentist', priority: TaskPriorityRegistry::HIGH),
        );

        self::assertSame(TaskPriorityRegistry::HIGH, $output->priority);
    }

    public function testItAcceptsAReferenceCategoryAndAPersonalOne(): void
    {
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $sideProject = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        $onReference = $this->useCase->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Call the dentist', categoryId: $home->id),
        );
        self::assertNotNull($onReference->category);
        self::assertFalse($onReference->category->isPersonal);

        $onPersonal = $this->useCase->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Ship the landing page', categoryId: $sideProject->id),
        );
        self::assertNotNull($onPersonal->category);
        self::assertTrue($onPersonal->category->isPersonal);
    }

    public function testItRefusesSomeoneElsesPersonalCategory(): void
    {
        $sideProject = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute(
                $this->currentUser($admin->id ?? 0),
                new CreateTaskDataInput('Peek at a category', categoryId: $sideProject->id),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryUsableConstraint::CATEGORY_NOT_FOUND,
                $exception->violations['categoryId'],
            );
        }
    }

    public function testItCreatesASubtask(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        $output = $this->useCase->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Return the keys', parentId: $parent->id),
        );

        self::assertSame($parent->id, $output->parentId);

        $reread = $this->taskProviderGateway->findOneById($parent->id ?? 0);
        self::assertNotNull($reread);
        self::assertCount(3, $reread->subtasks);
    }

    public function testItRefusesASubtaskOfASubtask(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);

        try {
            $this->useCase->execute(
                $this->currentUser($this->aliceId()),
                new CreateTaskDataInput('Too deep', parentId: $subtask->id),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                ParentTaskUsableConstraint::PARENT_TASK_IS_A_SUBTASK,
                $exception->violations['parentId'],
            );
        }
    }

    public function testItRefusesSomeoneElsesTaskAsAParent(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->useCase->execute($this->currentUser($admin->id ?? 0), new CreateTaskDataInput('Sneak in', parentId: $parent->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                ParentTaskUsableConstraint::PARENT_TASK_NOT_FOUND,
                $exception->violations['parentId'],
            );
        }
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        try {
            $this->useCase->execute($this->currentUser($this->aliceId()), new CreateTaskDataInput('', dueDate: 'nope'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
        }
    }

    public function testCreatingATaskStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $categoryId = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class)->id;
        $parentId = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new CreateTaskDataInput(
                title: 'Return the keys',
                priority: TaskPriorityRegistry::HIGH,
                categoryId: $categoryId,
                parentId: $parentId,
            )),
        );
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
