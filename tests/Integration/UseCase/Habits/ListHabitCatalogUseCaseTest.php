<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use App\UseCase\Habits\ListHabitCatalogUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The habits on offer, each flagged with whether one already keeps it. */
final class ListHabitCatalogUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHabitCatalogUseCase $catalog;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = self::getContainer()->get(ListHabitCatalogUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testTheCatalogFlagsWhatIsKept(): void
    {
        $subscribed = [];
        foreach ($this->catalog->execute($this->currentUser($this->idOf($this->alice))) as $item) {
            $subscribed[$item->name] = $item->isSubscribed;
        }

        self::assertTrue($subscribed['Lire']);
        self::assertTrue($subscribed['Marcher 5000 pas']);
        self::assertFalse($subscribed['Méditer']);
        // The retired habit is not offered.
        self::assertArrayNotHasKey('Ancienne habitude', $subscribed);
    }

    public function testListingTheCatalogStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->catalog->execute($currentUser),
        );
    }

    public function testListingTheCatalogDoesNotGrowWithIt(): void
    {
        $aliceId = $this->idOf($this->alice);
        $create = self::getContainer()->get(CreateHabitUseCase::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->catalog->execute($currentUser),
            function () use ($create): void {
                $create->execute($this->currentAdmin(), new CreateHabitDataInput('Écrire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL));
                $create->execute($this->currentAdmin(), new CreateHabitDataInput('Courir', HabitIconRegistry::RUN, HabitSourceRegistry::MANUAL));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }
}
