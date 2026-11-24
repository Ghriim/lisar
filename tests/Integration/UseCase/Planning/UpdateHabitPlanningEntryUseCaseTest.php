<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\HabitSubscribedConstraint;
use App\Domain\Validation\Validator\Planning\UpdateHabitPlanningEntryValidator;
use App\Fixtures\Habits\HabitFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\UpdateHabitPlanningEntryUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A habit entry replaced whole. */
final class UpdateHabitPlanningEntryUseCaseTest extends KernelTestCase
{
    use HabitPlanningTestTrait;
    use QueryCountTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** Another habit, another rule: what the new rule does not read goes. */
    public function testItReplacesTheEntryWhole(): void
    {
        $output = $this->update(new SaveHabitPlanningEntryDataInput($this->walkId(), new PlanningScheduleDataInput(PlanningRuleRegistry::ONCE, '2026-12-05', '2026-12-31', [1])));

        self::assertSame([$this->seededEntryId(), $this->walkId()], [$output->id, $output->habitId]);
        self::assertSame([PlanningRuleRegistry::ONCE, null, []], [$output->schedule->rule, $output->schedule->endDate, $output->schedule->weekdays]);

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertSame($this->walkId(), $this->thisWeek()->entries[0]->habitId);
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(UpdateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId(), $this->weekly($this->readingId()));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAHabitTheCallerDoesNotKeepIsRefused(): void
    {
        try {
            $this->update($this->weekly($this->habitId(HabitFixtures::MEDITATE)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHabitPlanningEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['habitId' => [HabitSubscribedConstraint::HABIT_NOT_SUBSCRIBED]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(UpdateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789, $this->weekly($this->readingId()));
    }

    public function testUpdatingStaysWithinItsQueryBudget(): void
    {
        $entryId = $this->seededEntryId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateHabitPlanningEntryUseCase::class)->execute($currentUser, $entryId, $this->weekly($this->readingId(), [2])),
            // The entry with its subscription and habit — the same one, not read again — then the UPDATE.
            max: 2,
        );
    }

    private function update(SaveHabitPlanningEntryDataInput $input): \App\Domain\DTO\Output\Planning\HabitPlanningEntryDataOutput
    {
        return $this->useCase(UpdateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId(), $input);
    }
}
