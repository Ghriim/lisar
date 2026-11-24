<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryCreatedEvent;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Validator\Planning\CreateTaskPlanningEntryValidator;
use App\Fixtures\Todo\CategoryFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Planning a task model on a rule of dates. */
final class CreateTaskPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RecordedEventsTrait;
    use TaskPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItPlansATaskModel(): void
    {
        $this->forgetRaisedEvents();
        $output = $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), new SaveTaskPlanningEntryDataInput(
            'Payer le loyer',
            'Virement.',
            $this->homeId(),
            new PlanningScheduleDataInput(rule: PlanningRuleRegistry::MONTHLY_DAY, startDate: '2026-12-01', monthDays: [5], startTime: '09:00', durationInMinutes: 15),
        ));

        self::assertSame(['Payer le loyer', 'Virement.', $this->homeId()], [$output->title, $output->description, $output->category?->id]);
        self::assertSame([PlanningRuleRegistry::MONTHLY_DAY, [5], '09:00', 15], [$output->schedule->rule, $output->schedule->monthDays, $output->schedule->startTime, $output->schedule->durationInMinutes]);
        self::assertSame($output->id, $this->assertEventRaised(TaskPlanningEntryCreatedEvent::class)->entry->id);

        // Read back.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $stored = self::getContainer()->get(TaskPlanningEntryProviderGateway::class)->findOneById($output->id);
        self::assertSame($this->aliceId, $stored?->owner->id);
        self::assertSame([$this->homeId(), 9 * 60], [$stored->category?->id, $stored->startTimeInMinutes]);
    }

    /** Someone else's personal category is answered as one that does not exist. */
    public function testACategoryTheCallerCannotUseIsRefused(): void
    {
        $this->assertRefused(
            ['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]],
            $this->everyDay(categoryId: $this->categoryId(CategoryFixtures::ALICE_SIDE_PROJECT)),
            $this->bobId,
        );
        $this->assertRefused(['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]], $this->everyDay(categoryId: 123456789));
    }

    public function testAModelOrAScheduleThatDoesNotHoldIsRefused(): void
    {
        $this->assertRefused(
            ['title' => ['title_required'], 'schedule.weekdays' => [PlanningScheduleConstraint::WEEKDAYS_REQUIRED]],
            new SaveTaskPlanningEntryDataInput('', null, null, new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-12-01')),
        );
    }

    public function testCreatingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($currentUser, $this->everyDay(categoryId: $this->homeId())),
            // The category, then the INSERT, in its transaction.
            max: 4,
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, SaveTaskPlanningEntryDataInput $input, ?int $userId = null): void
    {
        try {
            $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($userId ?? $this->aliceId), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTaskPlanningEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertEqualsCanonicalizing($violations, $exception->violations);
        }
    }
}
