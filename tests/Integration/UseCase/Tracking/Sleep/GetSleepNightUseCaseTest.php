<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Sleep;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Sleep\SaveSleepNightDataInput;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepNightProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Tracking\Sleep\SleepNightFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Sleep\GetSleepNightUseCase;
use App\UseCase\Tracking\Sleep\SaveSleepNightUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Reading the night of the day in progress: one night per waking day, and only today's is shown. */
final class GetSleepNightUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use SleepTestTrait;

    private GetSleepNightUseCase $get;
    private SaveSleepNightUseCase $save;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get = self::getContainer()->get(GetSleepNightUseCase::class);
        $this->save = self::getContainer()->get(SaveSleepNightUseCase::class);

        $this->loadFixtures(SleepNightFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    /**
     * Looking at the widget must not create anything, and the day is reported even when nothing
     * was noted on it: the page needs it to notice that the day has turned.
     */
    public function testItReportsAnEmptyNightWithoutWritingOne(): void
    {
        $output = $this->get->execute($this->currentUser($this->idOf($this->bob)));

        $clock = self::getContainer()->get(DayClock::class);
        self::assertSame($clock->today()->format('Y-m-d'), $output->day);
        self::assertNull($output->bedtimeAt);
        self::assertNull($output->wakeUpAt);
        self::assertNull($output->durationInMinutes);
        self::assertNull($output->moodRating);

        self::assertNull(
            self::getContainer()->get(SleepNightProviderGateway::class)
                ->findOneForOwnerAndDay($this->bob, $clock->today()),
        );
    }

    /**
     * The rule the whole tracker rests on: a night noted on another day is not this day's night,
     * however recent it is. A weight would still be shown; a night is not.
     */
    public function testANightFromAnotherDayIsNotTodaysNight(): void
    {
        $output = $this->get->execute($this->currentUser($this->idOf($this->alice)));

        self::assertNull($output->wakeUpAt);
        self::assertNull($output->durationInMinutes);
    }

    /** One person's night is not another's. */
    public function testNightsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->bob)), new SaveSleepNightDataInput('01:00', '09:00', 5));

        self::assertSame(480, $this->get->execute($this->currentUser($this->idOf($this->bob)))->durationInMinutes);
        self::assertNull($this->get->execute($this->currentUser($this->idOf($this->alice)))->durationInMinutes);
    }

    public function testReadingTheNightStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveSleepNightDataInput('23:30', '07:00', 4));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->get->execute($currentUser),
        );
    }
}
