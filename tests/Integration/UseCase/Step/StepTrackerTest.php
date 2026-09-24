<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Step;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\StepDayProviderGateway;
use App\Domain\Registry\Step\StepSourceRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\StepDayFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Step\GetStepDayUseCase;
use App\UseCase\Step\SaveStepDayUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The two ways a step count is read and written, together: they share one rule — one count per
 * day, cumulative, and only the day in progress can be written.
 */
final class StepTrackerTest extends KernelTestCase
{
    use LoadFixturesTrait;
    private const int DEFAULT_GOAL_IN_STEPS = 10000;

    private GetStepDayUseCase $getToday;
    private SaveStepDayUseCase $save;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getToday = self::getContainer()->get(GetStepDayUseCase::class);
        $this->save = self::getContainer()->get(SaveStepDayUseCase::class);

        $this->loadFixtures(StepDayFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    /**
     * Looking at the widget must not create anything: an account that has never recorded a count
     * has no row for today, and reading does not give it one. The goal is still shown, so the
     * widget has a target to draw against.
     */
    public function testItReportsAnEmptyDayWithoutWritingAnything(): void
    {
        $output = $this->getToday->execute($this->idOf($this->bob));

        self::assertNull($output->countInSteps);
        self::assertNull($output->source);
        self::assertSame(self::DEFAULT_GOAL_IN_STEPS, $output->goalInSteps);

        $clock = self::getContainer()->get(DayClock::class);
        self::assertSame($clock->today()->format('Y-m-d'), $output->day);
        self::assertNull(
            self::getContainer()->get(StepDayProviderGateway::class)
                ->findOneForOwnerAndDay($this->bob, $clock->today()),
        );
    }

    /**
     * A past day's row does not surface as today's: the tracker answers the day in progress, and
     * Alice's seeded day is two days back.
     */
    public function testAPastDayIsNotTodaysCount(): void
    {
        $output = $this->getToday->execute($this->idOf($this->alice));

        self::assertNull($output->countInSteps);
    }

    public function testItRecordsTodaysCount(): void
    {
        $output = $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(8432));

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
        $first = $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(8432));
        $second = $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(9000));

        self::assertSame(9000, $second->countInSteps);
        self::assertSame($first->day, $second->day);

        // The past day's row and today's, and nothing else: no third row was created.
        self::assertSame(2, $this->countStepDaysOf($this->alice));
    }

    /** The goal is frozen onto the day the moment it is first written. */
    public function testItFreezesTheGoalIntoTheDay(): void
    {
        $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(8432));

        $clock = self::getContainer()->get(DayClock::class);
        $stepDay = self::getContainer()->get(StepDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($stepDay);
        self::assertSame(self::DEFAULT_GOAL_IN_STEPS, $stepDay->goalInSteps);
    }

    public function testItRefusesACountOutOfRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(200001));
    }

    /** Nothing is written when the count is refused. */
    public function testARefusedCountLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(200001));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        $clock = self::getContainer()->get(DayClock::class);
        self::assertNull(
            self::getContainer()->get(StepDayProviderGateway::class)
                ->findOneForOwnerAndDay($this->alice, $clock->today()),
        );
    }

    public function testItRefusesAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->getToday->execute(0);
    }

    /** One person's steps are not another's: the count is read and written per owner. */
    public function testCountsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->idOf($this->bob), new SaveStepDayDataInput(5000));
        $this->save->execute($this->idOf($this->alice), new SaveStepDayDataInput(8432));

        self::assertSame(5000, $this->getToday->execute($this->idOf($this->bob))->countInSteps);
        self::assertSame(8432, $this->getToday->execute($this->idOf($this->alice))->countInSteps);
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }

    private function countStepDaysOf(UserDataModel $owner): int
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return (int) $entityManager->createQuery(
            'SELECT COUNT(stepDay.id) FROM App\Domain\DTO\DataModel\StepDayDataModel stepDay'
            .' WHERE stepDay.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
