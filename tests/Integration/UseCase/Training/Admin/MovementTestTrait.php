<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\Input\Training\CreateMovementDataInput;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFamilyFixtures;

/** What the tests of the movement back-office, and of what movements hold on to, share. */
trait MovementTestTrait
{
    /**
     * Floor press on dumbbells, in the Bench press family: a valid creation, varied one field at a time.
     *
     * @param list<int>|null $secondaryMuscleIds
     * @param list<int>|null $equipmentIds
     */
    private function create(
        string $name,
        ?array $secondaryMuscleIds = null,
        ?array $equipmentIds = null,
        bool $tracksReps = true,
    ): CreateMovementDataInput {
        return new CreateMovementDataInput(
            name: $name,
            movementFamilyId: $this->family(MovementFamilyFixtures::BENCH_PRESS)->id ?? 0,
            primaryMuscleId: $this->muscleNamed('Mid chest')->id ?? 0,
            description: 'Lying on the floor, press the dumbbells up.',
            videoUrl: 'https://example.com/floor-press',
            secondaryMuscleIds: $secondaryMuscleIds ?? [$this->muscleNamed('Triceps')->id ?? 0],
            equipmentIds: $equipmentIds ?? [$this->getReference(EquipmentFixtures::DUMBBELL, EquipmentDataModel::class)->id ?? 0],
            tracksReps: $tracksReps,
            tracksWeight: true,
        );
    }

    private function family(string $reference): MovementFamilyDataModel
    {
        return $this->getReference($reference, MovementFamilyDataModel::class);
    }

    private function movement(string $reference): MovementDataModel
    {
        return $this->getReference($reference, MovementDataModel::class);
    }

    private function muscleNamed(string $name): MuscleDataModel
    {
        $muscle = self::getContainer()->get(\App\Domain\Gateway\Provider\Training\MuscleProviderGateway::class)->findOneByName($name);
        self::assertNotNull($muscle);

        return $muscle;
    }

    private function equipmentNamed(string $name): EquipmentDataModel
    {
        $equipment = self::getContainer()->get(\App\Domain\Gateway\Provider\Training\EquipmentProviderGateway::class)->findOneByName($name);
        self::assertNotNull($equipment);

        return $equipment;
    }
}
