<?php

declare(strict_types=1);

namespace App\Fixtures\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The movement families, all active: one per movement the variants of which are worth following
 * together. MovementFixtures puts the movements in them, by name.
 */
final class MovementFamilyFixtures extends Fixture
{
    public const string BENCH_PRESS = 'movement-family-bench-press';
    public const string SQUAT = 'movement-family-squat';
    public const string PLANK = 'movement-family-plank';

    private const array NAMES = [
        'Bench press', 'Incline bench press', 'Chest press', 'Chest fly', 'Push-up', 'Dip', 'Pull-up',
        'Lat pulldown', 'Row', 'Deadlift', 'Romanian deadlift', 'Shrug', 'Back extension', 'Face pull',
        'Overhead press', 'Lateral raise', 'Rear delt fly', 'Curl', 'Hammer curl', 'Triceps extension',
        'Squat', 'Leg press', 'Leg extension', 'Leg curl', 'Lunge', 'Hip thrust', 'Hip abduction',
        'Hip adduction', 'Calf raise', 'Plank', 'Crunch', 'Leg raise', 'Ab rollout', 'Russian twist',
        'Carry', 'Running', 'Rowing', 'Cycling', 'Jump rope', 'Burpee', 'Box jump',
    ];

    private const array REFERENCES = [
        'Bench press' => self::BENCH_PRESS,
        'Squat' => self::SQUAT,
        'Plank' => self::PLANK,
    ];

    public function __construct(private readonly MovementFamilyPersisterGateway $movementFamilyPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::NAMES as $name) {
            $movementFamily = new MovementFamilyDataModel();
            $movementFamily->name = $name;

            $this->movementFamilyPersisterGateway->create($movementFamily);

            if (true === isset(self::REFERENCES[$name])) {
                $this->addReference(self::REFERENCES[$name], $movementFamily);
            }
        }
    }
}
