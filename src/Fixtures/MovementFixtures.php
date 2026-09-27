<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Domain\Gateway\Provider\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\MovementFamilyProviderGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LogicException;

use function sprintf;
use function str_contains;

/**
 * The common movements, all active, each a variant in its family. Families, muscles and
 * equipments are looked up by name, so the table below reads like the list it was agreed from.
 *
 * The measure is spelled in letters: R reps, W weight, T duration, D distance, and U for a
 * unilateral movement.
 */
final class MovementFixtures extends Fixture implements DependentFixtureInterface
{
    public const string BENCH_PRESS_BARBELL = 'movement-bench-press-barbell';
    public const string PUSH_UP = 'movement-push-up';
    public const string FARMER_WALK_DUMBBELL = 'movement-farmer-walk-dumbbell';

    /** family, name, primary muscle, secondary muscles, equipments, measure */
    private const array MOVEMENTS = [
        ['Bench press', 'Bench press (barbell)', 'Mid chest', ['Front delts', 'Triceps'], ['Barbell', 'Bench'], 'RW'],
        ['Bench press', 'Bench press (dumbbell)', 'Mid chest', ['Front delts', 'Triceps'], ['Dumbbell', 'Bench'], 'RW'],
        ['Incline bench press', 'Incline bench press (barbell)', 'Upper chest', ['Front delts', 'Triceps'], ['Barbell', 'Incline bench'], 'RW'],
        ['Incline bench press', 'Incline bench press (dumbbell)', 'Upper chest', ['Front delts', 'Triceps'], ['Dumbbell', 'Incline bench'], 'RW'],
        ['Chest press', 'Chest press (machine)', 'Mid chest', ['Front delts', 'Triceps'], ['Chest press machine'], 'RW'],
        ['Chest fly', 'Chest fly (machine)', 'Mid chest', ['Front delts'], ['Pec deck'], 'RW'],
        ['Chest fly', 'Chest fly (cable)', 'Mid chest', ['Front delts'], ['Cable machine'], 'RW'],
        ['Push-up', 'Push-up', 'Mid chest', ['Triceps', 'Front delts', 'Abs'], [], 'R'],
        ['Dip', 'Dip', 'Lower chest', ['Triceps', 'Front delts'], ['Dip bars'], 'R'],
        ['Pull-up', 'Pull-up', 'Lats', ['Biceps', 'Mid back'], ['Pull-up bar'], 'R'],
        ['Pull-up', 'Pull-up (assisted machine)', 'Lats', ['Biceps', 'Mid back'], ['Assisted pull-up machine'], 'RW'],
        ['Lat pulldown', 'Lat pulldown (machine)', 'Lats', ['Biceps', 'Mid back'], ['Lat pulldown machine'], 'RW'],
        ['Row', 'Bent-over row (barbell)', 'Mid back', ['Lats', 'Biceps', 'Rear delts'], ['Barbell'], 'RW'],
        ['Row', 'One-arm row (dumbbell)', 'Mid back', ['Lats', 'Biceps'], ['Dumbbell', 'Bench'], 'RWU'],
        ['Row', 'Seated row (machine)', 'Mid back', ['Lats', 'Biceps'], ['Seated row machine'], 'RW'],
        ['Row', 'T-bar row', 'Mid back', ['Lats', 'Biceps', 'Rear delts'], ['T-bar row machine'], 'RW'],
        ['Deadlift', 'Deadlift (barbell)', 'Glutes', ['Hamstrings', 'Lower back', 'Quadriceps', 'Traps', 'Forearms'], ['Barbell'], 'RW'],
        ['Deadlift', 'Deadlift (trap bar)', 'Glutes', ['Quadriceps', 'Hamstrings', 'Lower back', 'Traps'], ['Trap bar'], 'RW'],
        ['Romanian deadlift', 'Romanian deadlift (barbell)', 'Hamstrings', ['Glutes', 'Lower back'], ['Barbell'], 'RW'],
        ['Romanian deadlift', 'Romanian deadlift (dumbbell)', 'Hamstrings', ['Glutes', 'Lower back'], ['Dumbbell'], 'RW'],
        ['Shrug', 'Shrug (dumbbell)', 'Traps', ['Forearms'], ['Dumbbell'], 'RW'],
        ['Back extension', 'Back extension', 'Lower back', ['Glutes', 'Hamstrings'], ['Roman chair'], 'R'],
        ['Face pull', 'Face pull (cable)', 'Rear delts', ['Upper back', 'Traps'], ['Cable machine'], 'RW'],
        ['Overhead press', 'Overhead press (barbell)', 'Front delts', ['Side delts', 'Triceps'], ['Barbell'], 'RW'],
        ['Overhead press', 'Shoulder press (dumbbell)', 'Front delts', ['Side delts', 'Triceps'], ['Dumbbell', 'Bench'], 'RW'],
        ['Overhead press', 'Shoulder press (machine)', 'Front delts', ['Side delts', 'Triceps'], ['Shoulder press machine'], 'RW'],
        ['Lateral raise', 'Lateral raise (dumbbell)', 'Side delts', ['Traps'], ['Dumbbell'], 'RW'],
        ['Lateral raise', 'Lateral raise (cable)', 'Side delts', ['Traps'], ['Cable machine'], 'RWU'],
        ['Rear delt fly', 'Rear delt fly (machine)', 'Rear delts', ['Upper back'], ['Pec deck'], 'RW'],
        ['Curl', 'Curl (barbell)', 'Biceps', ['Forearms'], ['Barbell'], 'RW'],
        ['Curl', 'Curl (dumbbell)', 'Biceps', ['Forearms'], ['Dumbbell'], 'RW'],
        ['Curl', 'Curl (EZ bar)', 'Biceps', ['Forearms'], ['EZ bar'], 'RW'],
        ['Curl', 'Preacher curl (machine)', 'Biceps', ['Forearms'], ['Preacher curl machine'], 'RW'],
        ['Hammer curl', 'Hammer curl (dumbbell)', 'Biceps', ['Forearms'], ['Dumbbell'], 'RW'],
        ['Triceps extension', 'Triceps pushdown (cable)', 'Triceps', [], ['Cable machine'], 'RW'],
        ['Triceps extension', 'Skull crusher (EZ bar)', 'Triceps', [], ['EZ bar', 'Bench'], 'RW'],
        ['Triceps extension', 'Overhead triceps extension (dumbbell)', 'Triceps', [], ['Dumbbell'], 'RW'],
        ['Squat', 'Back squat (barbell)', 'Quadriceps', ['Glutes', 'Hamstrings', 'Lower back'], ['Barbell'], 'RW'],
        ['Squat', 'Front squat (barbell)', 'Quadriceps', ['Glutes', 'Abs'], ['Barbell'], 'RW'],
        ['Squat', 'Goblet squat (kettlebell)', 'Quadriceps', ['Glutes'], ['Kettlebell'], 'RW'],
        ['Squat', 'Hack squat (machine)', 'Quadriceps', ['Glutes'], ['Hack squat machine'], 'RW'],
        ['Leg press', 'Leg press (machine)', 'Quadriceps', ['Glutes', 'Hamstrings'], ['Leg press machine'], 'RW'],
        ['Leg extension', 'Leg extension (machine)', 'Quadriceps', [], ['Leg extension machine'], 'RW'],
        ['Leg curl', 'Leg curl (machine)', 'Hamstrings', ['Calves'], ['Leg curl machine'], 'RW'],
        ['Lunge', 'Lunge', 'Quadriceps', ['Glutes', 'Hamstrings'], [], 'RU'],
        ['Lunge', 'Lunge (dumbbell)', 'Quadriceps', ['Glutes', 'Hamstrings'], ['Dumbbell'], 'RWU'],
        ['Lunge', 'Bulgarian split squat (dumbbell)', 'Quadriceps', ['Glutes'], ['Dumbbell', 'Bench'], 'RWU'],
        ['Hip thrust', 'Hip thrust (barbell)', 'Glutes', ['Hamstrings'], ['Barbell', 'Bench'], 'RW'],
        ['Hip thrust', 'Hip thrust (machine)', 'Glutes', ['Hamstrings'], ['Hip thrust machine'], 'RW'],
        ['Hip abduction', 'Hip abduction (machine)', 'Abductors', ['Glutes'], ['Hip abductor machine'], 'RW'],
        ['Hip adduction', 'Hip adduction (machine)', 'Adductors', [], ['Hip adductor machine'], 'RW'],
        ['Calf raise', 'Calf raise (machine)', 'Calves', [], ['Calf raise machine'], 'RW'],
        ['Plank', 'Plank', 'Abs', ['Obliques'], [], 'T'],
        ['Plank', 'Side plank', 'Obliques', ['Abs'], [], 'TU'],
        ['Crunch', 'Crunch', 'Abs', [], [], 'R'],
        ['Crunch', 'Crunch (machine)', 'Abs', [], ['Ab crunch machine'], 'RW'],
        ['Leg raise', 'Hanging leg raise', 'Abs', ['Hip flexors', 'Forearms'], ['Pull-up bar'], 'R'],
        ['Ab rollout', 'Ab wheel rollout', 'Abs', ['Lats'], ['Ab wheel'], 'R'],
        ['Russian twist', 'Russian twist (medicine ball)', 'Obliques', ['Abs'], ['Medicine ball'], 'RW'],
        ['Carry', 'Farmer walk (dumbbell)', 'Full body', [], ['Dumbbell'], 'WD'],
        ['Carry', 'Farmer walk (kettlebell)', 'Full body', [], ['Kettlebell'], 'WD'],
        ['Running', 'Running', 'Cardio', ['Quadriceps', 'Calves'], [], 'TD'],
        ['Running', 'Running (treadmill)', 'Cardio', ['Quadriceps', 'Calves'], ['Treadmill'], 'TD'],
        ['Rowing', 'Rowing (machine)', 'Cardio', ['Lats', 'Quadriceps'], ['Rowing machine'], 'TD'],
        ['Cycling', 'Cycling (stationary bike)', 'Cardio', ['Quadriceps'], ['Stationary bike'], 'TD'],
        ['Jump rope', 'Jump rope', 'Cardio', ['Calves'], ['Jump rope'], 'T'],
        ['Burpee', 'Burpee', 'Full body', [], [], 'R'],
        ['Box jump', 'Box jump', 'Quadriceps', ['Glutes', 'Calves'], ['Plyo box'], 'R'],
    ];

    private const array REFERENCES = [
        'Bench press (barbell)' => self::BENCH_PRESS_BARBELL,
        'Push-up' => self::PUSH_UP,
        'Farmer walk (dumbbell)' => self::FARMER_WALK_DUMBBELL,
    ];

    public function __construct(
        private readonly MovementPersisterGateway $movementPersisterGateway,
        private readonly MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private readonly MuscleProviderGateway $muscleProviderGateway,
        private readonly EquipmentProviderGateway $equipmentProviderGateway,
    ) {
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [MovementFamilyFixtures::class, MuscleFixtures::class, EquipmentFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::MOVEMENTS as [$family, $name, $primary, $secondaries, $equipments, $measure]) {
            $movement = new MovementDataModel();
            $movement->name = $name;
            $movement->movementFamily = $this->family($family);
            $movement->primaryMuscle = $this->muscle($primary);
            foreach ($secondaries as $secondary) {
                $movement->secondaryMuscles->add($this->muscle($secondary));
            }
            foreach ($equipments as $equipment) {
                $movement->equipments->add($this->equipment($equipment));
            }
            $movement->tracksReps = str_contains($measure, 'R');
            $movement->tracksWeight = str_contains($measure, 'W');
            $movement->tracksDuration = str_contains($measure, 'T');
            $movement->tracksDistance = str_contains($measure, 'D');
            $movement->isUnilateral = str_contains($measure, 'U');

            $this->movementPersisterGateway->create($movement);

            if (true === isset(self::REFERENCES[$name])) {
                $this->addReference(self::REFERENCES[$name], $movement);
            }
        }
    }

    // A name missing from the seeded lists is a typo in the table above: say which, loudly.

    private function family(string $name): MovementFamilyDataModel
    {
        return $this->movementFamilyProviderGateway->findOneByName($name)
            ?? throw new LogicException(sprintf('No movement family named "%s".', $name));
    }

    private function muscle(string $name): MuscleDataModel
    {
        return $this->muscleProviderGateway->findOneByName($name)
            ?? throw new LogicException(sprintf('No muscle named "%s".', $name));
    }

    private function equipment(string $name): EquipmentDataModel
    {
        return $this->equipmentProviderGateway->findOneByName($name)
            ?? throw new LogicException(sprintf('No equipment named "%s".', $name));
    }
}
