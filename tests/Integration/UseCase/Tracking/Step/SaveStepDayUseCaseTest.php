<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Step;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Step\SaveStepDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Step\StepDayProviderGateway;
use App\Domain\Registry\Tracking\Step\StepSourceRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\Tracking\Step\StepDayFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Tracking\Step\SaveStepDayUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Recording today's step count: one count per day, cumulative, so recording again corrects it,
 * and only the day in progress can be written. The step habits follow the count.
 */
final class SaveStepDayUseCaseTest extends KernelTestCase
{
    use HabitTestTrait, StepTestTrait {
        StepTestTrait::idOf insteadof HabitTestTrait;
    }
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private SaveStepDayUseCase $save;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->save = self::getContainer()->get(SaveStepDayUseCase::class);

        // Alice keeps the 5000-step habit, which the tracker-habit tests watch.
        $this->loadFixtures(StepDayFixtures::class, HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItRecordsTodaysCount(): void
    {
        $output = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(8432));

        self::assertSame(8432, $output->countInSteps);
        self::assertSame(StepSourceRegistry::MANUAL, $output->source);
        self::assertSame(self::DEFAULT_GOAL_IN_STEPS, $output->goalInSteps);

        $clock = self::getContainer()->get(DayClock::class);
        self::assertSame($clock->today()->format('Y-m-d'), $output->day);

        $stepDay = self::getContainer()->get(StepDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($stepDay);
        self::assertSame(8432, $stepDay->countInSteps);
    }

    /**
     * One count per day: recording again overwrites the day's total instead of adding to it. This
     * is the whole reason there is a single write route — a cumulative count summed would double.
     */
    public function testRecordingTwiceCorrectsInsteadOfAdding(): void
    {
        $first = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(8432));
        $second = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(9000));

        self::assertSame(9000, $second->countInSteps);
        self::assertSame($first->day, $second->day);

        // The past day's row and today's, and nothing else: no third row was created.
        self::assertSame(2, $this->countStepDaysOf($this->alice));
    }

    /** The goal is frozen onto the day the moment it is first written. */
    public function testItFreezesTheGoalIntoTheDay(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(8432));

        $clock = self::getContainer()->get(DayClock::class);
        $stepDay = self::getContainer()->get(StepDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($stepDay);
        self::assertSame(self::DEFAULT_GOAL_IN_STEPS, $stepDay->goalInSteps);
    }

    public function testItRefusesACountOutOfRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(200001));
    }

    /** Nothing is written when the count is refused. */
    public function testARefusedCountLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(200001));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        $clock = self::getContainer()->get(DayClock::class);
        self::assertNull(
            self::getContainer()->get(StepDayProviderGateway::class)
                ->findOneForOwnerAndDay($this->alice, $clock->today()),
        );
    }

    public function testTheStepTrackerKeepsItsHabitWhenItCrossesTheMark(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(5000));

        self::assertTrue($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    public function testTheStepTrackerUnkeepsWhenCorrectedBelowTheMark(): void
    {
        // Over the 5000-step mark first, then corrected under it.
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(6000));
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(4000));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    /** Correcting today's count: read, rewritten, and the step habits kept to match. */
    public function testCorrectingTodaysCountStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveStepDayDataInput(4000));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->save->execute($currentUser, new SaveStepDayDataInput(8432)),
        );
    }

    private function countStepDaysOf(UserDataModel $owner): int
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return (int) $entityManager->createQuery(
            'SELECT COUNT(stepDay.id) FROM App\Domain\DTO\DataModel\Tracking\StepDayDataModel stepDay'
            .' WHERE stepDay.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
