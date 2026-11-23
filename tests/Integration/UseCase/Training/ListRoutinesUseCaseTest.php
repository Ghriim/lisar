<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\ListRoutinesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The caller's routines, by name, each with how many movements it holds. */
final class ListRoutinesUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** By name, accents and case aside; another account's routines are not listed. */
    public function testItListsTheCallersRoutinesByName(): void
    {
        $this->createRoutine('push');
        $this->addRoutineBlock($this->createRoutine('Épaules'), MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);
        $this->createRoutine('Bras');

        $routines = $this->useCase(ListRoutinesUseCase::class)->execute($this->currentUser($this->aliceId));

        self::assertSame(['Bras', 'Épaules', 'push'], array_map(static fn ($routine) => $routine->name, $routines));
        self::assertSame([0, 2, 0], array_map(static fn ($routine) => $routine->movementCount, $routines));
        self::assertSame([], $this->useCase(ListRoutinesUseCase::class)->execute($this->currentUser($this->idOf($this->bob))));
    }

    public function testListingTheRoutinesDoesNotGrowWithThem(): void
    {
        $this->addRoutineBlock($this->createRoutine('Push'), MovementFixtures::BENCH_PRESS_BARBELL);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListRoutinesUseCase::class)->execute($currentUser),
            fn () => $this->addRoutineBlock($this->createRoutine('Pull'), MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP),
        );

        self::assertCount(count($fewer) + 1, $more);
    }

    public function testListingTheRoutinesStaysWithinItsQueryBudget(): void
    {
        $this->benchRoutine();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListRoutinesUseCase::class)->execute($currentUser),
        );
    }
}
