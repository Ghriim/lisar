<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Domain\Validation\Constraint\Training\DefaultSetTypeKeptConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeUnusedConstraint;
use App\Domain\Validation\Validator\Training\CreateSetTypeValidator;
use App\Domain\Validation\Validator\Training\UpdateSetTypeValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\Admin\ActivateSetTypeUseCase;
use App\UseCase\Training\Admin\CreateSetTypeUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\Admin\DeleteSetTypeUseCase;
use App\UseCase\Training\Admin\ListSetTypesForAdminUseCase;
use App\UseCase\Training\Admin\UpdateSetTypeUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The back-office maintaining the kinds of set a workout can log. */
final class SetTypeBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    /** How many set types SetTypeFixtures seeds. */
    private const int SEEDED = 5;

    private ListSetTypesForAdminUseCase $list;
    private CreateSetTypeUseCase $create;
    private UpdateSetTypeUseCase $update;
    private DeactivateSetTypeUseCase $deactivate;
    private ActivateSetTypeUseCase $activate;
    private DeleteSetTypeUseCase $delete;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListSetTypesForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateSetTypeUseCase::class);
        $this->update = self::getContainer()->get(UpdateSetTypeUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateSetTypeUseCase::class);
        $this->activate = self::getContainer()->get(ActivateSetTypeUseCase::class);
        $this->delete = self::getContainer()->get(DeleteSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItListsEverySetTypeByName(): void
    {
        self::assertSame(['Back-off', 'Dropset', 'Échauffement', 'Échec', 'Travail'], $this->names(new ListSetTypesForAdminDataInput()));
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->deactivate->execute($this->dropset()->id ?? 0);

        self::assertCount(self::SEEDED, $this->list->execute(new ListSetTypesForAdminDataInput()));
        self::assertCount(self::SEEDED - 1, $this->list->execute(new ListSetTypesForAdminDataInput(isActive: true)));
        self::assertSame(['Dropset'], $this->names(new ListSetTypesForAdminDataInput(isActive: false)));
    }

    public function testItCreatesASetType(): void
    {
        $output = $this->create->execute(new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL));

        self::assertSame('Rest-pause', $output->name);
        self::assertSame(SetTypeColourRegistry::TEAL, $output->colour);
        self::assertTrue($output->isActive);
        self::assertFalse($output->isDefaultType);

        $setType = $this->setTypeProviderGateway->findOneById($output->id);
        self::assertNotNull($setType);
        self::assertSame(SetTypeColourRegistry::TEAL, $setType->colour);
    }

    /** "Dropset" and "dropset" are one name: the collation compares ignoring case. */
    public function testItRefusesANameAlreadyTakenIgnoringCase(): void
    {
        try {
            $this->create->execute(new CreateSetTypeDataInput('dropset', SetTypeColourRegistry::PINK));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItUpdatesASetType(): void
    {
        $output = $this->update->execute($this->dropset()->id ?? 0, new UpdateSetTypeDataInput('Drop set', SetTypeColourRegistry::PINK));

        self::assertSame('Drop set', $output->name);
        self::assertSame(SetTypeColourRegistry::PINK, $output->colour);

        $reread = $this->setTypeProviderGateway->findOneById($output->id);
        self::assertNotNull($reread);
        self::assertSame('Drop set', $reread->name);
        self::assertSame(SetTypeColourRegistry::PINK, $reread->colour);
    }

    /** A set type keeps its own name, and may change only its case. */
    public function testASetTypeMayKeepItsOwnName(): void
    {
        $output = $this->update->execute($this->dropset()->id ?? 0, new UpdateSetTypeDataInput('DROPSET', SetTypeColourRegistry::PURPLE));

        self::assertSame('DROPSET', $output->name);
    }

    public function testItRefusesToRenameOntoAnotherSetType(): void
    {
        try {
            $this->update->execute($this->dropset()->id ?? 0, new UpdateSetTypeDataInput('Échec', SetTypeColourRegistry::PURPLE));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItDeactivatesAndReactivatesASetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        self::assertFalse($this->deactivate->execute($id)->isActive);
        self::assertTrue($this->activate->execute($id)->isActive);
        self::assertTrue($this->setTypeProviderGateway->findOneById($id)?->isActive);
    }

    public function testItDeletesASetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        $this->delete->execute($id);

        self::assertNull($this->setTypeProviderGateway->findOneById($id));
    }

    /** A set type a logged set carries stays on it: deleting it is refused, anyone's set. */
    public function testItRefusesToDeleteASetTypeASetCarries(): void
    {
        $this->loadFixtures(SetTypeFixtures::class, MovementFixtures::class, UserFixtures::class);
        $id = $this->dropset()->id ?? 0;
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $pushUpId = $this->getReference(MovementFixtures::PUSH_UP, MovementDataModel::class)->id ?? 0;

        $workout = self::getContainer()->get(StartWorkoutUseCase::class)->execute($aliceId, new StartWorkoutDataInput());
        $workout = self::getContainer()->get(AddWorkoutBlockUseCase::class)->execute($aliceId, $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($pushUpId)]));
        self::getContainer()->get(AddWorkoutSetUseCase::class)->execute(
            $aliceId, $workout->id, $workout->blocks[0]->exercises[0]->id, new AddWorkoutSetDataInput(reps: 10, setTypeId: $id),
        );

        try {
            $this->delete->execute($id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeUnusedConstraint::IN_USE], $exception->violations['id']);
        }
    }

    /** Exactly one default: a type created as the default takes it from the one that had it. */
    public function testASetTypeCreatedAsTheDefaultTakesIt(): void
    {
        $output = $this->create->execute(new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL, isDefaultType: true));

        self::assertTrue($output->isDefaultType);
        self::assertSame($output->id, $this->setTypeProviderGateway->findOneDefault()?->id);
        self::assertFalse($this->working()->isDefaultType);
    }

    public function testAnUpdateGivesTheDefaultToAnotherSetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        $output = $this->update->execute($id, new UpdateSetTypeDataInput('Dropset', SetTypeColourRegistry::PURPLE, isDefaultType: true));

        self::assertTrue($output->isDefaultType);
        self::assertSame($id, $this->setTypeProviderGateway->findOneDefault()?->id);
        self::assertFalse($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isDefaultType);
    }

    /** The default is never unset, only given away: a set logged without a type has to get one. */
    public function testItRefusesToUnsetTheDefault(): void
    {
        try {
            $this->update->execute($this->working()->id ?? 0, new UpdateSetTypeDataInput('Travail', SetTypeColourRegistry::BLUE));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([DefaultSetTypeKeptConstraint::DEFAULT_REQUIRED], $exception->violations['isDefaultType']);
        }

        self::assertTrue($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isDefaultType);
    }

    public function testItRefusesToGiveTheDefaultToARetiredSetType(): void
    {
        $id = $this->dropset()->id ?? 0;
        $this->deactivate->execute($id);

        try {
            $this->update->execute($id, new UpdateSetTypeDataInput('Dropset', SetTypeColourRegistry::PURPLE, isDefaultType: true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([DefaultSetTypeKeptConstraint::DEFAULT_INACTIVE], $exception->violations['isDefaultType']);
        }
    }

    public function testItRefusesToDeactivateTheDefault(): void
    {
        try {
            $this->deactivate->execute($this->working()->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateSetTypeUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeNotDefaultConstraint::IS_THE_DEFAULT], $exception->violations['id']);
        }

        self::assertTrue($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isActive);
    }

    public function testItRefusesToDeleteTheDefault(): void
    {
        try {
            $this->delete->execute($this->working()->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeNotDefaultConstraint::IS_THE_DEFAULT], $exception->violations['id']);
        }

        self::assertNotNull($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0));
    }

    public function testItFailsOnAnUnknownSetType(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->update->execute(123456789, new UpdateSetTypeDataInput('Ghost', SetTypeColourRegistry::GREY));
    }

    /** @return list<string> */
    private function names(ListSetTypesForAdminDataInput $input): array
    {
        return array_map(static fn ($setType) => $setType->name, $this->list->execute($input));
    }

    private function working(): SetTypeDataModel
    {
        return $this->getReference(SetTypeFixtures::WORKING, SetTypeDataModel::class);
    }

    private function dropset(): SetTypeDataModel
    {
        return $this->getReference(SetTypeFixtures::DROPSET, SetTypeDataModel::class);
    }
}
