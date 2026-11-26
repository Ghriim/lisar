<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Validation\Constraint\Category\CategoryCommonConstraint;
use App\Fixtures\Category\CategoryFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Adding a habit to the catalogue, kept by hand or by a tracker crossing its mark. */
final class CreateHabitUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
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
            new CreateHabitDataInput('Écrire', HabitSourceRegistry::MANUAL),
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
            new CreateHabitDataInput('Marcher', HabitSourceRegistry::TRACKER),
        );
    }

    public function testItFilesTheHabitInACommonCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        $output = $this->create->execute(
            $this->currentAdmin(),
            new CreateHabitDataInput('Ranger', HabitSourceRegistry::MANUAL, categoryId: $home->id),
        );

        self::assertSame('Home', $output->category?->label);
        self::assertFalse($output->category->isPersonal);
    }

    /** The catalogue is everyone's: a personal label would show on one account only. */
    public function testItRefusesAPersonalCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);
        $personal = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        try {
            $this->create->execute(
                $this->currentAdmin(),
                new CreateHabitDataInput('Ranger', HabitSourceRegistry::MANUAL, categoryId: $personal->id),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['categoryId' => [CategoryCommonConstraint::CATEGORY_NOT_FOUND]], $exception->violations);
        }
    }

    public function testCreatingAHabitStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateHabitDataInput('Écrire', HabitSourceRegistry::MANUAL)),
        );
    }
}
