<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListMusclesForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateMuscleDataInput;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMuscleGroupUseCase;
use App\UseCase\Training\Admin\CreateMuscleUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleUseCase;
use App\UseCase\Training\Admin\ListMusclesForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function array_slice;

/** Listing the muscles for the back-office, filtered by status and group. */
final class ListMusclesForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    /** How many muscles MuscleFixtures seeds. */
    private const int SEEDED_MUSCLES = 25;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItListsEveryMuscleByGroupThenName(): void
    {
        $muscles = $this->listMuscles()->execute($this->currentAdmin());

        self::assertCount(self::SEEDED_MUSCLES, $muscles);
        // Arms first, and within it Biceps before Forearms before Triceps.
        self::assertSame(['Biceps', 'Forearms', 'Triceps'], array_map(static fn ($muscle) => $muscle->name, array_slice($muscles, 0, 3)));
        self::assertSame('Arms', $muscles[0]->muscleGroupName);
    }

    public function testTheMuscleListFiltersOnTheMusclesOwnFlag(): void
    {
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::LATS)->id ?? 0);
        // A deactivated group does not make its muscles inactive in this filter.
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $this->group(MuscleGroupFixtures::CHEST)->id ?? 0);

        self::assertCount(self::SEEDED_MUSCLES - 1, $this->listMuscles()->execute($this->currentAdmin(), new ListMusclesForAdminDataInput(isActive: true)));
        self::assertSame(['Lats'], $this->muscleNames(new ListMusclesForAdminDataInput(isActive: false)));
    }

    public function testTheMuscleListFiltersByGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);

        self::assertSame(
            ['Lower chest', 'Mid chest', 'Upper chest'],
            $this->muscleNames(new ListMusclesForAdminDataInput(muscleGroupId: $chest->id)),
        );
    }

    public function testTheGroupFilterCombinesWithTheStatusOne(): void
    {
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0);

        self::assertSame(
            ['Lower chest', 'Mid chest'],
            $this->muscleNames(new ListMusclesForAdminDataInput(isActive: true, muscleGroupId: $this->group(MuscleGroupFixtures::CHEST)->id)),
        );
    }

    /** An unknown group is a filter matching nothing, not an error. */
    public function testAnUnknownGroupFilterMatchesNothing(): void
    {
        self::assertSame([], $this->listMuscles()->execute($this->currentAdmin(), new ListMusclesForAdminDataInput(muscleGroupId: 123456789)));
    }

    public function testListingTheMusclesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->listMuscles()->execute($currentUser),
        );
    }

    /** Grown with muscles in groups of their own: a group read per muscle would show. */
    public function testListingTheMusclesDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->listMuscles()->execute($currentUser),
            function (): void {
                foreach (['Neck' => 'Sternocleidomastoid', 'Hands' => 'Grip'] as $group => $muscle) {
                    $groupId = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput($group))->id;
                    self::getContainer()->get(CreateMuscleUseCase::class)->execute($this->currentAdmin(), new CreateMuscleDataInput($muscle, $groupId));
                }
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }

    private function listMuscles(): ListMusclesForAdminUseCase
    {
        return self::getContainer()->get(ListMusclesForAdminUseCase::class);
    }

    /** @return list<string> */
    private function muscleNames(ListMusclesForAdminDataInput $input): array
    {
        return array_map(static fn ($muscle) => $muscle->name, $this->listMuscles()->execute($this->currentAdmin(), $input));
    }
}
