<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Planning\HabitSubscribedConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Validator\Planning\CreateHabitPlanningEntryValidator;
use App\Fixtures\Habits\HabitFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use App\UseCase\Planning\CreateHabitPlanningEntryUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Planning the days of a habit the caller keeps. */
final class CreateHabitPlanningEntryUseCaseTest extends KernelTestCase
{
    use HabitPlanningTestTrait;
    use QueryCountTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItPlansAHabit(): void
    {
        $output = $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), new SaveHabitPlanningEntryDataInput($this->walkId(), new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::MONTHLY_DAY,
            startDate: '2026-12-01',
            monthDays: [15, 1],
            startTime: '07:00',
            durationInMinutes: 45,
        )));

        self::assertSame([$this->walkId(), 'Marcher 5000 pas'], [$output->habitId, $output->habitName]);
        self::assertSame([PlanningRuleRegistry::MONTHLY_DAY, [1, 15], '07:00', 45], [$output->schedule->rule, $output->schedule->monthDays, $output->schedule->startTime, $output->schedule->durationInMinutes]);

        // Read back.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $stored = self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findOneById($output->id);
        self::assertSame($this->walkId(), $stored?->subscription->habit->id);
        self::assertSame($this->aliceId, $stored->owner->id);
        self::assertSame(7 * 60, $stored->startTimeInMinutes);
    }

    /** Not subscribed, dropped, retired from the catalogue, or someone else's: all answered as not kept. */
    public function testAHabitTheCallerDoesNotKeepIsRefused(): void
    {
        $refused = ['habitId' => [HabitSubscribedConstraint::HABIT_NOT_SUBSCRIBED]];

        $this->assertRefused($refused, $this->weekly($this->habitId(HabitFixtures::MEDITATE)));
        $this->assertRefused($refused, $this->weekly($this->habitId(HabitFixtures::RETIRED)));
        $this->assertRefused($refused, $this->weekly(123456789));
        $this->assertRefused($refused, $this->weekly($this->readingId()), $this->bobId);

        $this->useCase(UnsubscribeHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->walkId());
        $this->assertRefused($refused, $this->weekly($this->walkId()));
    }

    public function testAScheduleThatDoesNotHoldIsRefused(): void
    {
        $this->assertRefused(
            ['schedule.weekdays' => [PlanningScheduleConstraint::WEEKDAYS_REQUIRED]],
            new SaveHabitPlanningEntryDataInput($this->walkId(), new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-12-01')),
        );
    }

    public function testCreatingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($currentUser, $this->weekly($this->walkId())),
            // The habit, the caller's subscription to it, then the INSERT.
            max: 3,
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, SaveHabitPlanningEntryDataInput $input, ?int $userId = null): void
    {
        try {
            $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($userId ?? $this->aliceId), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateHabitPlanningEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertEqualsCanonicalizing($violations, $exception->violations);
        }
    }
}
