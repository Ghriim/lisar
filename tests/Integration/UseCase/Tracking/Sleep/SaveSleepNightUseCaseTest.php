<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Sleep;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Sleep\SaveSleepNightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Tracking\Sleep\SleepNightFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Sleep\GetSleepNightUseCase;
use App\UseCase\Tracking\Sleep\SaveSleepNightUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Noting the night that ended the day in progress: one night per waking day, so noting it again
 * corrects it, and only the day in progress can be written.
 */
final class SaveSleepNightUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use SleepTestTrait;

    private GetSleepNightUseCase $get;
    private SaveSleepNightUseCase $save;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get = self::getContainer()->get(GetSleepNightUseCase::class);
        $this->save = self::getContainer()->get(SaveSleepNightUseCase::class);

        $this->loadFixtures(SleepNightFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItNotesTheNightAcrossMidnight(): void
    {
        $output = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00', 4));

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
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00', 4));

        $output = $this->get->execute($this->currentUser($this->idOf($this->alice)));

        self::assertSame(450, $output->durationInMinutes);
        self::assertSame('23:30', $this->timeOf($output->bedtimeAt));
        self::assertSame('07:00', $this->timeOf($output->wakeUpAt));
    }

    /** One night per day: noting it again corrects it instead of adding a second one. */
    public function testNotingTwiceCorrectsInsteadOfAdding(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00', 4));
        $second = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('00:30', '08:00', 2));

        self::assertSame(450, $second->durationInMinutes);
        self::assertSame(2, $second->moodRating);
        self::assertSame('00:30', $this->timeOf($second->bedtimeAt));

        // The seeded past night and today's, and nothing else: no third row was created.
        self::assertSame(2, $this->countNightsOf($this->alice));
    }

    public function testItAcceptsANightWithNoMood(): void
    {
        $output = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00'));

        self::assertNull($output->moodRating);
        self::assertSame(450, $output->durationInMinutes);
    }

    /** Correcting is also how one takes the face back off a night already noted. */
    public function testItClearsAMoodThatIsNotSentAgain(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00', 4));
        $second = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('23:30', '07:00'));

        self::assertNull($second->moodRating);
    }

    public function testItRefusesAnImplausibleNight(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('07:30', '07:00'));
    }

    /** Nothing is written when the night is refused. */
    public function testARefusedNightLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveSleepNightDataInput('07:30', '07:00'));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        self::assertNull($this->get->execute($this->currentUser($this->idOf($this->alice)))->wakeUpAt);
    }

    /** Correcting the night already noted: read, then rewritten. */
    public function testCorrectingTheNightStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveSleepNightDataInput('23:30', '07:00', 4));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->save->execute($currentUser, new SaveSleepNightDataInput('00:30', '08:00', 2)),
        );
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
            'SELECT COUNT(sleepNight.id) FROM App\Domain\DTO\DataModel\Tracking\SleepNightDataModel sleepNight'
            .' WHERE sleepNight.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
