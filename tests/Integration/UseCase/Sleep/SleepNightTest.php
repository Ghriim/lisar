<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Sleep;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Sleep\SaveSleepNightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\SleepNightProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\SleepNightFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Sleep\GetSleepNightUseCase;
use App\UseCase\Sleep\SaveSleepNightUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The two ways a night is read and written, together: they share one rule — one night per waking
 * day, and only the day in progress can be written.
 */
final class SleepNightTest extends KernelTestCase
{
    use LoadFixturesTrait;

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
        $output = $this->get->execute($this->idOf($this->bob));

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
        $output = $this->get->execute($this->idOf($this->alice));

        self::assertNull($output->wakeUpAt);
        self::assertNull($output->durationInMinutes);
    }

    public function testItNotesTheNightAcrossMidnight(): void
    {
        $output = $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00', 4));

        $clock = self::getContainer()->get(DayClock::class);
        $today = $clock->today();

        self::assertSame($today->format('Y-m-d'), $output->day);
        self::assertSame(450, $output->durationInMinutes);
        self::assertSame(4, $output->moodRating);

        // The bedtime is the evening before, shown on the clock the day is counted on.
        self::assertSame($today->modify('-1 day')->format('Y-m-d').' 23:30', $this->wallClock($output->bedtimeAt));
        self::assertSame($today->format('Y-m-d').' 07:00', $this->wallClock($output->wakeUpAt));
    }

    /** Read back through the gateway: the instants were really persisted as instants. */
    public function testTheNightIsReadBackAsItWasNoted(): void
    {
        $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00', 4));

        $output = $this->get->execute($this->idOf($this->alice));

        self::assertSame(450, $output->durationInMinutes);
        self::assertSame('23:30', $this->timeOf($output->bedtimeAt));
        self::assertSame('07:00', $this->timeOf($output->wakeUpAt));
    }

    /** One night per day: noting it again corrects it instead of adding a second one. */
    public function testNotingTwiceCorrectsInsteadOfAdding(): void
    {
        $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00', 4));
        $second = $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('00:30', '08:00', 2));

        self::assertSame(450, $second->durationInMinutes);
        self::assertSame(2, $second->moodRating);
        self::assertSame('00:30', $this->timeOf($second->bedtimeAt));

        // The seeded past night and today's, and nothing else: no third row was created.
        self::assertSame(2, $this->countNightsOf($this->alice));
    }

    public function testItAcceptsANightWithNoMood(): void
    {
        $output = $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00'));

        self::assertNull($output->moodRating);
        self::assertSame(450, $output->durationInMinutes);
    }

    /** Correcting is also how one takes the face back off a night already noted. */
    public function testItClearsAMoodThatIsNotSentAgain(): void
    {
        $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00', 4));
        $second = $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('23:30', '07:00'));

        self::assertNull($second->moodRating);
    }

    public function testItRefusesAnImplausibleNight(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('07:30', '07:00'));
    }

    /** Nothing is written when the night is refused. */
    public function testARefusedNightLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->idOf($this->alice), new SaveSleepNightDataInput('07:30', '07:00'));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        self::assertNull($this->get->execute($this->idOf($this->alice))->wakeUpAt);
    }

    public function testItRefusesAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->get->execute(0);
    }

    /** One person's night is not another's. */
    public function testNightsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->idOf($this->bob), new SaveSleepNightDataInput('01:00', '09:00', 5));

        self::assertSame(480, $this->get->execute($this->idOf($this->bob))->durationInMinutes);
        self::assertNull($this->get->execute($this->idOf($this->alice))->durationInMinutes);
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }

    /** The day and hour the API reports, which it has already converted to the display zone. */
    private function wallClock(?string $moment): string
    {
        return null === $moment ? '' : substr($moment, 0, 10).' '.substr($moment, 11, 5);
    }

    private function timeOf(?string $moment): string
    {
        return null === $moment ? '' : substr($moment, 11, 5);
    }

    private function countNightsOf(UserDataModel $owner): int
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return (int) $entityManager->createQuery(
            'SELECT COUNT(sleepNight.id) FROM App\Domain\DTO\DataModel\SleepNightDataModel sleepNight'
            .' WHERE sleepNight.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
