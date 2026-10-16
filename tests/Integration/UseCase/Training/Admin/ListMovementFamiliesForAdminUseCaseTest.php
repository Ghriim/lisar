<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMovementFamilyUseCase;
use App\UseCase\Training\Admin\ListMovementFamiliesForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Listing the movement families for the back-office. */
final class ListMovementFamiliesForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    /** How many families the fixtures seed. */
    private const int SEEDED_FAMILIES = 41;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItListsEveryFamily(): void
    {
        self::assertCount(self::SEEDED_FAMILIES, self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute($this->currentAdmin()));
    }

    public function testListingTheFamiliesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute($currentUser),
        );
    }

    public function testListingTheFamiliesDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute($currentUser),
            function (): void {
                self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($this->currentAdmin(), new CreateMovementFamilyDataInput('Clean'));
                self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($this->currentAdmin(), new CreateMovementFamilyDataInput('Snatch'));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }
}
