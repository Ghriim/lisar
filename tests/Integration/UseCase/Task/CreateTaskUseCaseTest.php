<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\TagProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Domain\Validation\Constraint\Task\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Task\ParentTaskUsableConstraint;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\PriorityFixtures;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CreateTaskUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreateTaskUseCase $useCase;
    private TaskProviderGateway $taskProviderGateway;
    private TagProviderGateway $tagProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateTaskUseCase::class);
        $this->taskProviderGateway = self::getContainer()->get(TaskProviderGateway::class);
        $this->tagProviderGateway = self::getContainer()->get(TagProviderGateway::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItCreatesATask(): void
    {
        $output = $this->useCase->execute($this->aliceId(), new CreateTaskDataInput(
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
        $task = $this->taskProviderGateway->findOneByIdForOwner($output->id, $this->alice);
        self::assertNotNull($task);
        self::assertSame('Call the dentist', $task->title);
        self::assertSame('2026-10-02', $task->dueDate?->format('Y-m-d'));
        self::assertFalse($task->isDone());
    }

    /**
     * The back-office default applies when the caller names no priority.
     */
    public function testItAppliesTheDefaultPriority(): void
    {
        $output = $this->useCase->execute($this->aliceId(), new CreateTaskDataInput('Call the dentist'));

        self::assertNotNull($output->priority);
        self::assertSame('Normal', $output->priority->label);
        self::assertTrue($output->priority->isDefault);
    }

    public function testItKeepsThePriorityTheCallerAsksFor(): void
    {
        $high = $this->getReference(PriorityFixtures::HIGH, PriorityDataModel::class);

        $output = $this->useCase->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Call the dentist', priorityId: $high->id),
        );

        self::assertNotNull($output->priority);
        self::assertSame('High', $output->priority->label);
    }

    public function testItAcceptsAReferenceCategoryAndAPersonalOne(): void
    {
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $sideProject = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        $onReference = $this->useCase->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Call the dentist', categoryId: $home->id),
        );
        self::assertNotNull($onReference->category);
        self::assertFalse($onReference->category->isPersonal);

        $onPersonal = $this->useCase->execute(
            $this->aliceId(),
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
                $admin->id ?? 0,
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

    public function testItReusesTheAccountsTagsAndCreatesTheNewOnes(): void
    {
        $output = $this->useCase->execute($this->aliceId(), new CreateTaskDataInput(
            title: 'Call the dentist',
            // "urgent" already exists on the account, "health" does not.
            tags: ['urgent', 'health', '  urgent  '],
        ));

        self::assertSame(['health', 'urgent'], $output->tags);

        $labels = [];
        foreach ($this->tagProviderGateway->findAllForOwner($this->alice) as $tag) {
            $labels[] = $tag->label;
        }

        // "urgent" was not created a second time.
        self::assertSame(['errand', 'health', 'urgent'], $labels);
    }

    public function testItCreatesASubtask(): void
    {
        $parent = $this->getReference(TaskFixtures::ALICE_WITH_SUBTASKS, TaskDataModel::class);

        $output = $this->useCase->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Return the keys', parentId: $parent->id),
        );

        self::assertSame($parent->id, $output->parentId);

        $reread = $this->taskProviderGateway->findOneByIdForOwner($parent->id ?? 0, $this->alice);
        self::assertNotNull($reread);
        self::assertCount(3, $reread->subtasks);
    }

    public function testItRefusesASubtaskOfASubtask(): void
    {
        $subtask = $this->getReference(TaskFixtures::ALICE_SUBTASK_OPEN, TaskDataModel::class);

        try {
            $this->useCase->execute(
                $this->aliceId(),
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
            $this->useCase->execute($admin->id ?? 0, new CreateTaskDataInput('Sneak in', parentId: $parent->id));
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
            $this->useCase->execute($this->aliceId(), new CreateTaskDataInput('', dueDate: 'nope'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
        }
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
