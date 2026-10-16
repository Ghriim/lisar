<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Dropping a habit one keeps: it leaves the panel, its days stay. */
final class UnsubscribeHabitUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UnsubscribeHabitUseCase $unsubscribe;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unsubscribe = self::getContainer()->get(UnsubscribeHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testUnsubscribingFromAHabitStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $reading = $this->habitId(HabitFixtures::READING);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->unsubscribe->execute($currentUser, $reading),
        );
    }
}
