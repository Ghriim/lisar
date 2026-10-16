<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Adding a habit to the catalogue, kept by hand or by a tracker crossing its mark. */
final class CreateHabitUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateHabitUseCase $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateHabitUseCase::class);
    }

    public function testItCreatesAManualHabit(): void
    {
        $output = $this->create->execute(
            $this->currentAdmin(),
            new CreateHabitDataInput('Écrire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL),
        );

        self::assertSame('Écrire', $output->name);
        self::assertSame(HabitSourceRegistry::MANUAL, $output->sourceKind);
        self::assertNull($output->trackerKind);
        self::assertTrue($output->isActive);
    }

    public function testItCreatesATrackerHabit(): void
    {
        $output = $this->create->execute($this->currentAdmin(), new CreateHabitDataInput(
            'Marcher',
            HabitIconRegistry::RUN,
            HabitSourceRegistry::TRACKER,
            HabitTrackerRegistry::STEPS,
            8000,
        ));

        self::assertSame(HabitSourceRegistry::TRACKER, $output->sourceKind);
        self::assertSame(HabitTrackerRegistry::STEPS, $output->trackerKind);
        self::assertSame(8000, $output->trackerThreshold);
    }

    public function testItRefusesATrackerHabitWithoutItsMark(): void
    {
        $this->expectException(ValidationException::class);

        $this->create->execute(
            $this->currentAdmin(),
            new CreateHabitDataInput('Marcher', HabitIconRegistry::RUN, HabitSourceRegistry::TRACKER),
        );
    }

    public function testCreatingAHabitStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateHabitDataInput('Écrire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL)),
        );
    }
}
