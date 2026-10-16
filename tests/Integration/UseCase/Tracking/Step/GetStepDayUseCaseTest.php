<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Step;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Step\SaveStepDayDataInput;
use App\Domain\Gateway\Provider\Tracking\Step\StepDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Tracking\Step\StepDayFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Step\GetStepDayUseCase;
use App\UseCase\Tracking\Step\SaveStepDayUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Reading the step count of the day in progress, and the goal it is drawn against. */
final class GetStepDayUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use StepTestTrait;

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
        $output = $this->getToday->execute($this->currentUser($this->idOf($this->bob)));

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
        $output = $this->getToday->execute($this->currentUser($this->idOf($this->alice)));

        self::assertNull($output->countInSteps);
    }

    /** One person's steps are not another's: the count is read and written per owner. */
    public function testCountsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->bob)), new SaveStepDayDataInput(5000));
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput(8432));

        self::assertSame(5000, $this->getToday->execute($this->currentUser($this->idOf($this->bob)))->countInSteps);
        self::assertSame(8432, $this->getToday->execute($this->currentUser($this->idOf($this->alice)))->countInSteps);
    }

    public function testReadingTodaysCountStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveStepDayDataInput(4000));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->getToday->execute($currentUser),
        );
    }
}
