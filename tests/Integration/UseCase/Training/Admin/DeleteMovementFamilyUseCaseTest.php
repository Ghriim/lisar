<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MovementFamilyUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteMovementFamilyValidator;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMovementFamilyUseCase;
use App\UseCase\Training\Admin\DeleteMovementFamilyUseCase;
use App\UseCase\Training\Admin\ListMovementFamiliesForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a movement family, refused while it holds movements. */
final class DeleteMovementFamilyUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItDeletesAnEmptyFamily(): void
    {
        $family = self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($this->currentAdmin(), new CreateMovementFamilyDataInput('Clean'));

        self::getContainer()->get(DeleteMovementFamilyUseCase::class)->execute($this->currentAdmin(), $family->id);

        self::assertNotContains('Clean', array_map(
            static fn ($output) => $output->name,
            self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute($this->currentAdmin()),
        ));
    }

    public function testItRefusesToDeleteAFamilyHoldingMovements(): void
    {
        try {
            self::getContainer()->get(DeleteMovementFamilyUseCase::class)->execute($this->currentAdmin(), $this->family(MovementFamilyFixtures::PLANK)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementFamilyValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementFamilyUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }

    /** Refused on the movements it holds: the usage check is part of the budget. */
    public function testDeletingAFamilyStaysWithinItsQueryBudget(): void
    {
        $id = self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($this->currentAdmin(), new CreateMovementFamilyDataInput('Clean'))->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeleteMovementFamilyUseCase::class)->execute($currentUser, $id),
        );
    }
}
