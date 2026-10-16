<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Weight;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Weight\SaveWeightDataInput;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Tracking\Weight\WeightEntryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Weight\GetLatestWeightUseCase;
use App\UseCase\Tracking\Weight\SaveWeightUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Reading the last known weight, with the day it belongs to — today's or a past one. */
final class GetLatestWeightUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use WeightTestTrait;

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
        $output = $this->getLatest->execute($this->currentUser($this->idOf($this->bob)));

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
        $output = $this->getLatest->execute($this->currentUser($this->idOf($this->alice)));

        self::assertSame(WeightEntryFixtures::ALICE_WEIGHT_IN_KILOGRAMS, $output->weightInKilograms);
        self::assertFalse($output->isFromToday);

        $expectedDay = self::getContainer()->get(DayClock::class)->today()
            ->modify(sprintf('-%d days', WeightEntryFixtures::DAYS_AGO));
        self::assertSame($expectedDay->format('Y-m-d'), $output->day);
    }

    /** Today's weight is the latest, even though a past day's row exists too. */
    public function testTodaysWeightBecomesTheLastKnownOne(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->alice)), new SaveWeightDataInput(71.85));

        $output = $this->getLatest->execute($this->currentUser($this->idOf($this->alice)));

        self::assertSame(71.85, $output->weightInKilograms);
        self::assertTrue($output->isFromToday);
    }

    /** One person's scale is not another's: the latest weight is read per owner. */
    public function testWeightsAreOwnedByOneAccount(): void
    {
        $this->save->execute($this->currentUser($this->idOf($this->bob)), new SaveWeightDataInput(80.0));

        self::assertSame(80.0, $this->getLatest->execute($this->currentUser($this->idOf($this->bob)))->weightInKilograms);
        self::assertSame(
            WeightEntryFixtures::ALICE_WEIGHT_IN_KILOGRAMS,
            $this->getLatest->execute($this->currentUser($this->idOf($this->alice)))->weightInKilograms,
        );
    }

    public function testReadingTheLatestWeightStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->save->execute($this->currentUser($aliceId), new SaveWeightDataInput(71.85));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->getLatest->execute($currentUser),
        );
    }
}
