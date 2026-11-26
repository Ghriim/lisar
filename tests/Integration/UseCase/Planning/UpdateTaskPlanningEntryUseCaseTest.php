<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryUpdatedEvent;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Validation\Constraint\Category\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Category\CategoryFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Planning\UpdateTaskPlanningEntryUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Replacing a task entry whole: its model and its schedule. */
final class UpdateTaskPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RecordedEventsTrait;
    use TaskPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItReplacesTheEntry(): void
    {
        $this->forgetRaisedEvents();
        $output = $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId(), $this->everyDay('Sortir le compost', startTime: '19:30'));

        self::assertSame(['Sortir le compost', null, null], [$output->title, $output->description, $output->category]);
        self::assertSame([[1, 2, 3, 4, 5, 6, 7], '19:30'], [$output->schedule->weekdays, $output->schedule->startTime]);
        self::assertSame($this->seededEntryId(), $this->assertEventRaised(TaskPlanningEntryUpdatedEvent::class)->entry->id);

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $stored = self::getContainer()->get(TaskPlanningEntryProviderGateway::class)->findOneById($this->seededEntryId());
        self::assertSame(['Sortir le compost', null], [$stored?->title, $stored->category]);
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId(), $this->everyDay());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testACategoryTheCallerCannotUseIsRefused(): void
    {
        $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId(), $this->everyDay(categoryId: $this->categoryId(CategoryFixtures::ALICE_SIDE_PROJECT)));

        try {
            $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId(), $this->everyDay(categoryId: 123456789));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789, $this->everyDay());
    }

    public function testUpdatingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            // Its category kept: not read again.
            fn (UserDataModel $currentUser) => $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($currentUser, $this->seededEntryId(), $this->everyDay(categoryId: $this->homeId())),
            // The entry with its category, then the UPDATE, in its transaction.
            max: 4,
        );
    }
}
