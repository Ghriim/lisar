<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Weight;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Weight\SaveWeightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Tracking\Weight\WeightEntryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Weight\SaveWeightUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Recording today's weight: one weight per day, so recording again corrects it, and only the day
 * in progress can be written.
 */
final class SaveWeightUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use WeightTestTrait;

    private SaveWeightUseCase $save;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->save = self::getContainer()->get(SaveWeightUseCase::class);

        $this->loadFixtures(WeightEntryFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItRecordsTodaysWeight(): void
    {
        $output = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(71.85));

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

    /**
     * One weight per day: recording again corrects the day's measurement instead of adding a
     * second one. This is the whole reason there is a single write route.
     */
    public function testRecordingTwiceCorrectsInsteadOfAdding(): void
    {
        $first = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(71.85));
        $second = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(72.1));

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
        $first = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(71.85));
        $second = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(72.1));

        self::assertSame($first->recordedAt, $second->recordedAt);
    }

    /** The column keeps two decimals, so that is what is stored — not whatever was typed. */
    public function testItKeepsTwoDecimals(): void
    {
        $output = $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(71.8549));

        self::assertSame(71.85, $output->weightInKilograms);
    }

    public function testItRefusesAWeightOutOfRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(724.0));
    }

    /** Nothing is written when the weight is refused. */
    public function testARefusedWeightLeavesTheDayEmpty(): void
    {
        try {
            $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(724.0));
        } catch (ValidationException) {
            // The point of the test is what follows.
        }

        $clock = self::getContainer()->get(DayClock::class);
        self::assertNull(
            self::getContainer()->get(WeightEntryProviderGateway::class)
                ->findOneForOwnerAndDay($this->alice, $clock->today()),
        );
    }

    /** Correcting today's weight: read, then rewritten. */
    public function testCorrectingTodaysWeightStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveWeightDataInput(71.85));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->save->execute($currentUser, new SaveWeightDataInput(72.1)),
        );
    }

    private function countEntriesOf(UserDataModel $owner): int
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return (int) $entityManager->createQuery(
            'SELECT COUNT(weightEntry.id) FROM App\Domain\DTO\DataModel\Tracking\WeightEntryDataModel weightEntry'
            .' WHERE weightEntry.owner = :owner',
        )->setParameter('owner', $owner)->getSingleScalarResult();
    }
}
