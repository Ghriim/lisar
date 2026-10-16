<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MovementFamilyNameAvailableConstraint;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMovementFamilyUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating a movement family, under a name no other family has. */
final class CreateMovementFamilyUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItRefusesAFamilyNameAlreadyTakenIgnoringCase(): void
    {
        try {
            self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($this->currentAdmin(), new CreateMovementFamilyDataInput('bench press'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MovementFamilyNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testCreatingAFamilyStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute($currentUser, new CreateMovementFamilyDataInput('Clean')),
        );
    }
}
