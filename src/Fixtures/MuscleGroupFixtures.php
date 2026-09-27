<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The muscle groups, all active. "Other" holds what is not a muscle group as such — cardio, and the
 * whole body for the movements that work it all at once.
 */
final class MuscleGroupFixtures extends Fixture
{
    public const string CHEST = 'muscle-group-chest';
    public const string BACK = 'muscle-group-back';
    public const string SHOULDERS = 'muscle-group-shoulders';
    public const string ARMS = 'muscle-group-arms';
    public const string CORE = 'muscle-group-core';
    public const string LEGS = 'muscle-group-legs';
    public const string GLUTES = 'muscle-group-glutes';
    public const string OTHER = 'muscle-group-other';

    private const array GROUPS = [
        self::CHEST => 'Chest',
        self::BACK => 'Back',
        self::SHOULDERS => 'Shoulders',
        self::ARMS => 'Arms',
        self::CORE => 'Core',
        self::LEGS => 'Legs',
        self::GLUTES => 'Glutes',
        self::OTHER => 'Other',
    ];

    public function __construct(private readonly MuscleGroupPersisterGateway $muscleGroupPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::GROUPS as $reference => $name) {
            $muscleGroup = new MuscleGroupDataModel();
            $muscleGroup->name = $name;

            $this->addReference($reference, $this->muscleGroupPersisterGateway->create($muscleGroup));
        }
    }
}
