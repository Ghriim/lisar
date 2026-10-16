<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\DeleteHydrationEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a drink from the day in progress. The day itself stays, an empty day being a fact too. */
final class DeleteHydrationEntryUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateHydrationEntryUseCase $create;
    private DeleteHydrationEntryUseCase $delete;
    private SubscribeHabitUseCase $subscribe;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);
        $this->delete = self::getContainer()->get(DeleteHydrationEntryUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);

        // The habit subscriptions are for the tracker-habit test: Alice keeps no hydration habit
        // until a test subscribes her.
        $this->loadFixtures(UserFixtures::class, HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItRemovesAnEntryAndKeepsTheDay(): void
    {
        $created = $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));

        $output = $this->delete->execute($this->currentUser($this->aliceId()), $created->entries[0]->id);

        self::assertSame(0, $output->totalInMillilitres);
        self::assertSame([], $output->entries);

        // The day itself stays: an empty day is a fact too.
        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($day);
    }

    public function testAnotherAccountsEntryIsRefused(): void
    {
        $created = $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        try {
            $this->delete->execute($this->currentUser($admin->id ?? 0), $created->entries[0]->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testTheHydrationTrackerUnkeepsWhenADrinkIsRemovedBelowTheMark(): void
    {
        $this->subscribe->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::DRINK));
        $this->drink(1000);
        $day = $this->drink(600);

        self::getContainer()->get(DeleteHydrationEntryUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->entryId($day, 600));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testRemovingADrinkStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(250));
        $entryId = $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(500))->entries[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->delete->execute($currentUser, $entryId),
        );
    }
}
