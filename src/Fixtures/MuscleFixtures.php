<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The muscles, all active, in common gym names and at the precision a workout is planned at —
 * upper and lower chest, not the pectoralis minor.
 */
final class MuscleFixtures extends Fixture implements DependentFixtureInterface
{
    public const string UPPER_CHEST = 'muscle-upper-chest';
    public const string LATS = 'muscle-lats';
    public const string QUADRICEPS = 'muscle-quadriceps';
    public const string CARDIO = 'muscle-cardio';

    /**
     * @var array<string, array<string>>
     */
    private const array MUSCLES = [
        MuscleGroupFixtures::CHEST => ['Upper chest', 'Mid chest', 'Lower chest'],
        MuscleGroupFixtures::BACK => ['Lats', 'Traps', 'Upper back', 'Mid back', 'Lower back'],
        MuscleGroupFixtures::SHOULDERS => ['Front delts', 'Side delts', 'Rear delts'],
        MuscleGroupFixtures::ARMS => ['Biceps', 'Triceps', 'Forearms'],
        MuscleGroupFixtures::CORE => ['Abs', 'Obliques'],
        MuscleGroupFixtures::LEGS => ['Quadriceps', 'Hamstrings', 'Adductors', 'Abductors', 'Calves', 'Hip flexors'],
        MuscleGroupFixtures::GLUTES => ['Glutes'],
        MuscleGroupFixtures::OTHER => ['Cardio'],
    ];

    private const array REFERENCES = [
        'Upper chest' => self::UPPER_CHEST,
        'Lats' => self::LATS,
        'Quadriceps' => self::QUADRICEPS,
        'Cardio' => self::CARDIO,
    ];

    public function __construct(private readonly MusclePersisterGateway $musclePersisterGateway)
    {
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [MuscleGroupFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::MUSCLES as $groupReference => $names) {
            $muscleGroup = $this->getReference($groupReference, MuscleGroupDataModel::class);

            foreach ($names as $name) {
                $muscle = new MuscleDataModel();
                $muscle->name = $name;
                $muscle->muscleGroup = $muscleGroup;

                $this->musclePersisterGateway->create($muscle);

                if (true === isset(self::REFERENCES[$name])) {
                    $this->addReference(self::REFERENCES[$name], $muscle);
                }
            }
        }
    }
}
