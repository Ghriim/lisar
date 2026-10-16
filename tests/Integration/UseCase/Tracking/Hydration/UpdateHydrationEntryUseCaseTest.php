<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\UpdateHydrationEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a drink of the day in progress, and answering the whole day. */
final class UpdateHydrationEntryUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateHydrationEntryUseCase $create;
    private UpdateHydrationEntryUseCase $update;
    private SubscribeHabitUseCase $subscribe;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);
        $this->update = self::getContainer()->get(UpdateHydrationEntryUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);

        // The habit subscriptions are for the tracker-habit test: Alice keeps no hydration habit
        // until a test subscribes her.
        $this->loadFixtures(UserFixtures::class, HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItCorrectsAnEntry(): void
    {
        $created = $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));
        $entryId = $created->entries[0]->id;

        $output = $this->update->execute($this->currentUser($this->aliceId()), $entryId, new UpdateHydrationEntryDataInput(300));

        self::assertSame(300, $output->totalInMillilitres);
    }

    public function testTheHydrationTrackerUnkeepsWhenADrinkIsCorrectedBelowTheMark(): void
    {
        $this->subscribe->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::DRINK));
        $this->drink(1000);
        $day = $this->drink(600);

        self::getContainer()->get(UpdateHydrationEntryUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->entryId($day, 600), new UpdateHydrationEntryDataInput(300),
        );

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testCorrectingADrinkStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(250));
        $entryId = $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(500))->entries[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->update->execute($currentUser, $entryId, new UpdateHydrationEntryDataInput(300)),
        );
    }
}
