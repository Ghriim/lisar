<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Todo\TodoUpdatedEvent;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Fixtures\Planning\TaskPlanningEntryFixtures;
use App\Fixtures\Todo\TodoFixtures;
use App\Infrastructure\EventHandler\Planning\DeleteTodoPlanningEntriesEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Todo\TodoTestTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Todo\ArchiveTodoUseCase;
use App\UseCase\Todo\UpdateTodoUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Archiving « Maison » takes the entries planning its tasks; « A faire »'s bins stay. */
final class DeleteTodoPlanningEntriesEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private int $plantsEntryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(TaskPlanningEntryFixtures::class);
        $this->plantsEntryId = $this->withHandlers(fn () => self::getContainer()->get(CreateTaskPlanningEntryUseCase::class)->execute(
            $this->currentUser($this->aliceId()),
            new SaveTaskPlanningEntryDataInput(
                title: 'Arroser les plantes',
                schedule: new PlanningScheduleDataInput(rule: PlanningRuleRegistry::WEEKLY, startDate: '2026-11-02', weekdays: [7]),
                todoId: $this->todoId(TodoFixtures::ALICE_HOME),
            ),
        ))->id;
    }

    public function testArchivingTheTodoDeletesItsEntriesOnly(): void
    {
        $this->handle($this->eventRaisedBy(TodoUpdatedEvent::class, fn () => $this->archive()));

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $entries = self::getContainer()->get(TaskPlanningEntryProviderGateway::class);
        self::assertNull($entries->findOneById($this->plantsEntryId));
        self::assertNotNull($entries->findOneById($this->getReference(TaskPlanningEntryFixtures::ALICE_BINS_MONDAY_THURSDAY, TaskPlanningEntryDataModel::class)->id ?? 0));
    }

    /** Renaming writes the TODO too, and leaves its planning alone. */
    public function testRenamingDeletesNothing(): void
    {
        $event = $this->eventRaisedBy(TodoUpdatedEvent::class, fn () => self::getContainer()->get(UpdateTodoUseCase::class)->execute(
            $this->currentUser($this->aliceId()),
            $this->todoId(TodoFixtures::ALICE_HOME),
            new SaveTodoDataInput('Appartement'),
        ));

        self::assertFalse($event->archives);
        $this->assertQueryCountAtMost(fn () => $event, fn (EventInterface $event) => $this->handle($event), max: 0);
    }

    public function testDeletingTheEntriesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(TodoUpdatedEvent::class, fn () => $this->archive()),
            fn (EventInterface $event) => $this->handle($event),
            // One DELETE, whatever the number of entries.
            max: 1,
        );
    }

    private function handler(): DeleteTodoPlanningEntriesEventHandler
    {
        return self::getContainer()->get(DeleteTodoPlanningEntriesEventHandler::class);
    }

    private function archive(): void
    {
        self::getContainer()->get(ArchiveTodoUseCase::class)->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_HOME));
    }
}
