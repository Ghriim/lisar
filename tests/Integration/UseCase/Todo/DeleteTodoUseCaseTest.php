<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoNotDefaultConstraint;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\Todo\TodoFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Todo\DeleteTodoUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteTodoUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use RecordedEventsTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private DeleteTodoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(DeleteTodoUseCase::class);
        $this->loadFixtures(TaskFixtures::class);
    }

    /** Its tasks, their subtasks and the entries planning them go with it, by the database's cascade. */
    public function testItDeletesTheTodoWithItsTasksAndItsPlanning(): void
    {
        $entry = self::getContainer()->get(CreateTaskPlanningEntryUseCase::class)->execute(
            $this->currentUser($this->aliceId()),
            new SaveTaskPlanningEntryDataInput(
                title: 'Arroser les plantes',
                schedule: new PlanningScheduleDataInput(rule: PlanningRuleRegistry::WEEKLY, startDate: '2026-11-02', weekdays: [7]),
                todoId: $this->todoId(TodoFixtures::ALICE_HOME),
            ),
        );

        $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_HOME));

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(self::getContainer()->get(TodoProviderGateway::class)->findOneById($this->todoId(TodoFixtures::ALICE_HOME)));
        $tasks = self::getContainer()->get(TaskProviderGateway::class);
        foreach ([TaskFixtures::ALICE_WITH_SUBTASKS, TaskFixtures::ALICE_SUBTASK_DONE, TaskFixtures::ALICE_SUBTASK_OPEN] as $reference) {
            self::assertNull($tasks->findOneById($this->taskId($reference)));
        }
        self::assertNull(self::getContainer()->get(TaskPlanningEntryProviderGateway::class)->findOneById($entry->id));
        // The other TODOs' tasks stay.
        self::assertNotNull($tasks->findOneById($this->taskId(TaskFixtures::ALICE_PLAIN)));
    }

    /** An archived TODO may be deleted too. */
    public function testItDeletesAnArchivedTodo(): void
    {
        $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_ARCHIVED));

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(self::getContainer()->get(TaskProviderGateway::class)->findOneById($this->taskId(TaskFixtures::ALICE_ARCHIVED)));
    }

    public function testItRefusesTheDefault(): void
    {
        $this->assertRefused(['id' => [TodoNotDefaultConstraint::TODO_IS_DEFAULT]], $this->todoId(TodoFixtures::ALICE_DEFAULT), $this->aliceId());
    }

    public function testItRefusesSomeoneElsesTodo(): void
    {
        $this->assertRefused(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $this->todoId(TodoFixtures::ALICE_HOME), $this->adminId());
    }

    public function testATodoThatDoesNotExistIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), 999999);
    }

    public function testDeletingStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $todoId = $this->todoId(TodoFixtures::ALICE_HOME);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $todoId),
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, int $todoId, int $callerId): void
    {
        try {
            $this->useCase->execute($this->currentUser($callerId), $todoId);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame($violations, $exception->violations);
        }
    }
}
