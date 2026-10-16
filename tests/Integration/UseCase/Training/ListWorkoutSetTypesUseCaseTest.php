<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\Admin\CreateSetTypeUseCase;
use App\UseCase\Training\ListWorkoutSetTypesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The set types a set can take: those still on offer. */
final class ListWorkoutSetTypesUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testListingTheSetTypesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutSetTypesUseCase::class)->execute($currentUser),
        );
    }

    public function testListingTheSetTypesDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutSetTypesUseCase::class)->execute($currentUser),
            function (): void {
                foreach (['Rest-pause', 'Cluster', 'Myo-reps'] as $name) {
                    $this->useCase(CreateSetTypeUseCase::class)->execute($this->currentAdmin(), new CreateSetTypeDataInput($name, SetTypeColourRegistry::TEAL));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }
}
