<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Fixtures\Habits\HabitFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\DeactivateHabitUseCase;
use App\UseCase\Habits\Admin\ListHabitsForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;

/** Retiring a habit: no longer offered, but the row stays. */
final class DeactivateHabitUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHabitsForAdminUseCase $list;
    private DeactivateHabitUseCase $deactivate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsForAdminUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateHabitUseCase::class);
    }

    public function testDeactivatingRetiresItFromTheOfferedCatalog(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $reading = $this->getReference(HabitFixtures::READING, HabitDataModel::class);

        $before = count(self::getContainer()->get(HabitProviderGateway::class)->findAllActive());

        $output = $this->deactivate->execute($this->currentAdmin(), $reading->id ?? 0);

        self::assertFalse($output->isActive);
        self::assertSame(
            $before - 1,
            count(self::getContainer()->get(HabitProviderGateway::class)->findAllActive()),
        );
        // The row stays: the admin list still holds it.
        self::assertCount(5, $this->list->execute($this->currentAdmin()));
    }

    public function testItFailsOnAnUnknownHabit(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->deactivate->execute($this->currentAdmin(), 123456789);
    }

    public function testDeactivatingAHabitStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $readingId = $this->getReference(HabitFixtures::READING, HabitDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->deactivate->execute($currentUser, $readingId),
        );
    }
}
