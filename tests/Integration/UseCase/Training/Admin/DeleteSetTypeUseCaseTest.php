<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteSetTypeValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\Admin\DeleteSetTypeUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a set type, refused for the default one and once a logged set carries it. */
final class DeleteSetTypeUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    private DeleteSetTypeUseCase $delete;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->delete = self::getContainer()->get(DeleteSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItDeletesASetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        $this->delete->execute($this->currentAdmin(), $id);

        self::assertNull($this->setTypeProviderGateway->findOneById($id));
    }

    /** A set type a logged set carries stays on it: deleting it is refused, anyone's set. */
    public function testItRefusesToDeleteASetTypeASetCarries(): void
    {
        $this->loadFixtures(SetTypeFixtures::class, MovementFixtures::class, UserFixtures::class);
        $id = $this->dropset()->id ?? 0;
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $pushUpId = $this->getReference(MovementFixtures::PUSH_UP, MovementDataModel::class)->id ?? 0;

        $workout = self::getContainer()->get(StartWorkoutUseCase::class)->execute($this->currentUser($aliceId), new StartWorkoutDataInput());
        $workout = self::getContainer()->get(AddWorkoutBlockUseCase::class)->execute($this->currentUser($aliceId), $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($pushUpId)]));
        self::getContainer()->get(AddWorkoutSetUseCase::class)->execute(
            $this->currentUser($aliceId), $workout->id, $workout->blocks[0]->exercises[0]->id, new AddWorkoutSetDataInput(reps: 10, setTypeId: $id),
        );

        try {
            $this->delete->execute($this->currentAdmin(), $id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeUnusedConstraint::IN_USE], $exception->violations['id']);
        }
    }

    public function testItRefusesToDeleteTheDefault(): void
    {
        try {
            $this->delete->execute($this->currentAdmin(), $this->working()->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeNotDefaultConstraint::IS_THE_DEFAULT], $exception->violations['id']);
        }

        self::assertNotNull($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0));
    }

    public function testDeletingASetTypeStaysWithinItsQueryBudget(): void
    {
        $id = $this->dropset()->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->delete->execute($currentUser, $id),
        );
    }
}
