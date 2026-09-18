<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Weight;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\WeightEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\UserFixtures;
use App\Fixtures\WeightEntryFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Weight\GetLatestWeightUseCase;
use App\UseCase\Weight\SaveWeightUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The two ways a weight is read and written, together: they share one rule — one weight per day,
 * and only the day in progress can be written.
 */
final class WeightTrackerTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private GetLatestWeightUseCase $getLatest;
    private SaveWeightUseCase $save;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getLatest = self::getContainer()->get(GetLatestWeightUseCase::class);
        $this->save = self::getContainer()->get(SaveWeightUseCase::class);

        $this->loadFixtures(WeightEntryFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    /**
     * Looking at the widget must not create anything: an account that has never weighed itself
     * has no row, and reading does not give it one.
     */
    public function testItReportsNothingWithoutWritingAnything(): void
    {
        $output = $this->getLatest->execute($this->idOf($this->bob));

        self::assertNull($output->weightInKilograms);
        self::assertNull($output->day);
        self::assertNull($output->recordedAt);
        self::assertFalse($output->isFromToday);

        self::assertNull(
            self::getContainer()->get(WeightEntryProviderGateway::class)->findLatestForOwner($this->bob),
        );
    }

    /**
     * The case the widget exists for: nothing was recorded today, and the last known weight is
     * shown with the day it belongs to rather than hidden.
     */
    public function testItReportsTheLastKnownWeightFromAPastDay(): void
    {
        $output = $this->getLatest->execute($this->idOf($this->alice));

        self::assertSame(WeightEntryFixtures::ALICE_WEIGHT_IN_KILOGRAMS, $output->weightInKilograms);
        self::assertFalse($output->isFromToday);

        $expectedDay = self::getContainer()->get(DayClock::class)->today()
            ->modify(sprintf('-%d days', WeightEntryFixtures::DAYS_AGO));
        self::assertSame($expectedDay->format('Y-m-d'), $output->day);
    }

    public function testItRecordsTodaysWeight(): void
    {
        $output = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(71.85));

        self::assertSame(71.85, $output->weightInKilograms);
        self::assertTrue($output->isFromToday);

        $clock = self::getContainer()->get(DayClock::class);
        self::assertSame($clock->today()->format('Y-m-d'), $output->day);

        // Re-read through the gateway: a decimal column must come back as the number that was
        // written, not as a string and not as a drifted float.
        $entry = self::getContainer()->get(WeightEntryProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($entry);
        self::assertSame(71.85, $entry->weightInKilograms);
    }

    /** Today's weight is the latest, even though a past day's row exists too. */
    public function testTodaysWeightBecomesTheLastKnownOne(): void
    {
        $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(71.85));

        $output = $this->getLatest->execute($this->idOf($this->alice));

        self::assertSame(71.85, $output->weightInKilograms);
        self::assertTrue($output->isFromToday);
    }

    /**
     * One weight per day: recording again corrects the day's measurement instead of adding a
     * second one. This is the whole reason there is a single write route.
     */
    public function testRecordingTwiceCorrectsInsteadOfAdding(): void
    {
        $first = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(71.85));
        $second = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(72.1));

        self::assertSame(72.1, $second->weightInKilograms);
        self::assertSame($first->day, $second->day);

        // The past day's row and today's, and nothing else: no third row was created.
        self::assertSame(2, $this->countEntriesOf($this->alice));
    }

    /**
     * Correcting a typo at noon does not mean the person weighed themselves at noon: the moment
     * belongs to the measurement, not to the edit.
     */
    public function testCorrectingDoesNotMoveTheMomentItWasRecorded(): void
    {
        $first = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(71.85));
        $second = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(72.1));

        self::assertSame($first->recordedAt, $second->recordedAt);
    }

    /** The column keeps two decimals, so that is what is stored — not whatever was typed. */
    public function testItKeepsTwoDecimals(): void
    {
        $output = $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(71.8549));

        self::assertSame(71.85, $output->weightInKilograms);
    }

    public function testItRefusesAWeightOutOfRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(724.0));
    }

    /** Nothing is written when the weight is refused. */
    public function testARefusedWeightLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->idOf($this->alice), new SaveWeightDataInput(724.0));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        $clock = self::getContainer()->get(DayClock::class);
        self::assertNull(
            self::getContainer()->get(WeightEntryProviderGateway::class)
                ->findOneForOwnerAndDay($this->alice, $clock->today()),
        );
    }

    public function testItRefusesAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->getLatest->execute(0);
    }

    /** One person's scale is not another's: the latest weight is read per owner. */
    public function testWeightsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->idOf($this->bob), new SaveWeightDataInput(80.0));

        self::assertSame(80.0, $this->getLatest->execute($this->idOf($this->bob))->weightInKilograms);
        self::assertSame(
            WeightEntryFixtures::ALICE_WEIGHT_IN_KILOGRAMS,
            $this->getLatest->execute($this->idOf($this->alice))->weightInKilograms,
        );
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }

    private function countEntriesOf(UserDataModel $owner): int
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return (int) $entityManager->createQuery(
            'SELECT COUNT(weightEntry.id) FROM App\Domain\DTO\DataModel\WeightEntryDataModel weightEntry'
            .' WHERE weightEntry.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
