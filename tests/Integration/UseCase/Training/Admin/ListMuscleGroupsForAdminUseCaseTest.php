<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Fixtures\Training\MuscleFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMuscleGroupUseCase;
use App\UseCase\Training\Admin\ListMuscleGroupsForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Listing the muscle groups for the back-office. */
final class ListMuscleGroupsForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItListsEveryGroupByName(): void
    {
        self::assertSame(
            ['Arms', 'Back', 'Chest', 'Core', 'Glutes', 'Legs', 'Other', 'Shoulders'],
            array_map(static fn ($group) => $group->name, $this->listGroups()->execute($this->currentAdmin())),
        );
    }

    public function testListingTheGroupsStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->listGroups()->execute($currentUser),
        );
    }

    public function testListingTheGroupsDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->listGroups()->execute($currentUser),
            function (): void {
                self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('Neck'));
                self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('Hands'));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }

    private function listGroups(): ListMuscleGroupsForAdminUseCase
    {
        return self::getContainer()->get(ListMuscleGroupsForAdminUseCase::class);
    }
}
