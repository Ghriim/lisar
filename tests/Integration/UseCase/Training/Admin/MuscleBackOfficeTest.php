<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\Input\Training\Admin\ListMusclesForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateMuscleDataInput;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\DTO\Input\Training\UpdateMuscleDataInput;
use App\Domain\DTO\Input\Training\UpdateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupUnusedConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use App\Domain\Validation\Constraint\Training\MuscleNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMuscleGroupValidator;
use App\Domain\Validation\Validator\Training\CreateMuscleValidator;
use App\Domain\Validation\Validator\Training\UpdateMuscleValidator;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Training\Admin\ActivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\CreateMuscleGroupUseCase;
use App\UseCase\Training\Admin\CreateMuscleUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleUseCase;
use App\UseCase\Training\Admin\DeleteMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeleteMuscleUseCase;
use App\UseCase\Training\Admin\ListMuscleGroupsForAdminUseCase;
use App\UseCase\Training\Admin\ListMusclesForAdminUseCase;
use App\UseCase\Training\Admin\UpdateMuscleGroupUseCase;
use App\UseCase\Training\Admin\UpdateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function array_slice;

/** The back-office maintaining muscles and the groups they sit in — one screen, one test. */
final class MuscleBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    /** How many muscles MuscleFixtures seeds. */
    private const int SEEDED_MUSCLES = 25;

    private MuscleProviderGateway $muscleProviderGateway;
    private MuscleGroupProviderGateway $muscleGroupProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleProviderGateway = self::getContainer()->get(MuscleProviderGateway::class);
        $this->muscleGroupProviderGateway = self::getContainer()->get(MuscleGroupProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    // ------------------------------------------------------------------ groups

    public function testItListsEveryGroupByName(): void
    {
        self::assertSame(
            ['Arms', 'Back', 'Chest', 'Core', 'Glutes', 'Legs', 'Other', 'Shoulders'],
            array_map(static fn ($group) => $group->name, $this->listGroups()->execute()),
        );
    }

    public function testItCreatesAGroup(): void
    {
        $output = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute(new CreateMuscleGroupDataInput('Neck'));

        self::assertSame('Neck', $output->name);
        self::assertTrue($output->isActive);
        self::assertNotNull($this->muscleGroupProviderGateway->findOneById($output->id));
    }

    public function testItRefusesAGroupNameAlreadyTakenIgnoringCase(): void
    {
        try {
            self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute(new CreateMuscleGroupDataInput('chest'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItRenamesAGroup(): void
    {
        $output = self::getContainer()->get(UpdateMuscleGroupUseCase::class)
            ->execute($this->group(MuscleGroupFixtures::OTHER)->id ?? 0, new UpdateMuscleGroupDataInput('Miscellaneous'));

        self::assertSame('Miscellaneous', $output->name);
        self::assertSame('Miscellaneous', $this->muscleGroupProviderGateway->findOneById($output->id)?->name);
    }

    /** Deactivating a group withdraws its muscles without touching their own flag. */
    public function testDeactivatingAGroupLeavesItsMusclesOwnFlagAlone(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);

        self::assertFalse(self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($chest->id ?? 0)->isActive);

        $upperChest = $this->muscleProviderGateway->findOneById($this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0);
        self::assertNotNull($upperChest);
        self::assertTrue($upperChest->isActive);
        self::assertFalse($upperChest->muscleGroup->isActive);

        self::assertTrue(self::getContainer()->get(ActivateMuscleGroupUseCase::class)->execute($chest->id ?? 0)->isActive);
    }

    public function testItDeletesAnEmptyGroup(): void
    {
        $neck = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute(new CreateMuscleGroupDataInput('Neck'));

        self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($neck->id);

        self::assertNull($this->muscleGroupProviderGateway->findOneById($neck->id));
    }

    /** Inactive muscles count too: every muscle sits in a group, whatever its status. */
    public function testItRefusesToDeleteAGroupHoldingMuscles(): void
    {
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->muscle(MuscleFixtures::CARDIO)->id ?? 0);

        try {
            self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($this->group(MuscleGroupFixtures::OTHER)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMuscleGroupUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupUnusedConstraint::MUSCLE_GROUP_IN_USE, $exception->violations['id']);
        }
    }

    public function testItFailsOnAnUnknownGroup(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute(123456789);
    }

    // ----------------------------------------------------------------- muscles

    public function testItListsEveryMuscleByGroupThenName(): void
    {
        $muscles = $this->listMuscles()->execute();

        self::assertCount(self::SEEDED_MUSCLES, $muscles);
        // Arms first, and within it Biceps before Forearms before Triceps.
        self::assertSame(['Biceps', 'Forearms', 'Triceps'], array_map(static fn ($muscle) => $muscle->name, array_slice($muscles, 0, 3)));
        self::assertSame('Arms', $muscles[0]->muscleGroupName);
    }

    public function testTheMuscleListFiltersOnTheMusclesOwnFlag(): void
    {
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->muscle(MuscleFixtures::LATS)->id ?? 0);
        // A deactivated group does not make its muscles inactive in this filter.
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->group(MuscleGroupFixtures::CHEST)->id ?? 0);

        self::assertCount(self::SEEDED_MUSCLES - 1, $this->listMuscles()->execute(new ListMusclesForAdminDataInput(isActive: true)));
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
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0);

        self::assertSame(
            ['Lower chest', 'Mid chest'],
            $this->muscleNames(new ListMusclesForAdminDataInput(isActive: true, muscleGroupId: $this->group(MuscleGroupFixtures::CHEST)->id)),
        );
    }

    /** An unknown group is a filter matching nothing, not an error. */
    public function testAnUnknownGroupFilterMatchesNothing(): void
    {
        self::assertSame([], $this->listMuscles()->execute(new ListMusclesForAdminDataInput(muscleGroupId: 123456789)));
    }

    public function testItCreatesAMuscleInAGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);

        $output = self::getContainer()->get(CreateMuscleUseCase::class)->execute(new CreateMuscleDataInput('Serratus', $chest->id ?? 0));

        self::assertSame('Serratus', $output->name);
        self::assertSame($chest->id, $output->muscleGroupId);
        self::assertSame('Chest', $output->muscleGroupName);
        self::assertTrue($output->muscleGroupIsActive);
        self::assertSame('Chest', $this->muscleProviderGateway->findOneById($output->id)?->muscleGroup->name);
    }

    /** Unique across every group, not only within its own. */
    public function testItRefusesAMuscleNameTakenInAnotherGroup(): void
    {
        try {
            self::getContainer()->get(CreateMuscleUseCase::class)
                ->execute(new CreateMuscleDataInput('lats', $this->group(MuscleGroupFixtures::SHOULDERS)->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItRefusesAnUnknownGroup(): void
    {
        try {
            self::getContainer()->get(CreateMuscleUseCase::class)->execute(new CreateMuscleDataInput('Serratus', 123456789));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_NOT_FOUND, $exception->violations['muscleGroupId']);
        }
    }

    public function testItRefusesToCreateAMuscleInAnInactiveGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($chest->id ?? 0);

        try {
            self::getContainer()->get(CreateMuscleUseCase::class)->execute(new CreateMuscleDataInput('Serratus', $chest->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    public function testItMovesAMuscleToAnotherGroup(): void
    {
        $legs = $this->group(MuscleGroupFixtures::LEGS);

        $output = self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute($this->muscle(MuscleFixtures::CARDIO)->id ?? 0, new UpdateMuscleDataInput('Cardio', $legs->id ?? 0));

        self::assertSame($legs->id, $output->muscleGroupId);
        self::assertSame('Legs', $this->muscleProviderGateway->findOneById($output->id)?->muscleGroup->name);
    }

    public function testItRefusesToMoveAMuscleIntoAnInactiveGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($chest->id ?? 0);

        try {
            self::getContainer()->get(UpdateMuscleUseCase::class)
                ->execute($this->muscle(MuscleFixtures::LATS)->id ?? 0, new UpdateMuscleDataInput('Lats', $chest->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    /** Staying in the inactive group it already sits in is not a move. */
    public function testAMuscleInAnInactiveGroupCanStillBeRenamed(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($chest->id ?? 0);

        $output = self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute($this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0, new UpdateMuscleDataInput('Clavicular chest', $chest->id ?? 0));

        self::assertSame('Clavicular chest', $output->name);
        self::assertFalse($output->muscleGroupIsActive);
    }

    public function testItDeletesAMuscle(): void
    {
        $id = $this->muscle(MuscleFixtures::QUADRICEPS)->id ?? 0;

        self::getContainer()->get(DeleteMuscleUseCase::class)->execute($id);

        self::assertNull($this->muscleProviderGateway->findOneById($id));
    }

    public function testItFailsOnAnUnknownMuscle(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute(123456789, new UpdateMuscleDataInput('Ghost', $this->group(MuscleGroupFixtures::CHEST)->id ?? 0));
    }

    private function listGroups(): ListMuscleGroupsForAdminUseCase
    {
        return self::getContainer()->get(ListMuscleGroupsForAdminUseCase::class);
    }

    private function listMuscles(): ListMusclesForAdminUseCase
    {
        return self::getContainer()->get(ListMusclesForAdminUseCase::class);
    }

    /** @return list<string> */
    private function muscleNames(ListMusclesForAdminDataInput $input): array
    {
        return array_map(static fn ($muscle) => $muscle->name, $this->listMuscles()->execute($input));
    }

    private function group(string $reference): MuscleGroupDataModel
    {
        return $this->getReference($reference, MuscleGroupDataModel::class);
    }

    private function muscle(string $reference): MuscleDataModel
    {
        return $this->getReference($reference, MuscleDataModel::class);
    }
}
