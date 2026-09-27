<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\Gateway\Persister\EquipmentPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The equipment a gym and a living room hold, all active: what carries a load, what does not, and
 * the three cardio machines that track a distance. Machines are one row each, not one generic
 * "machine": the movement done on it depends on which one it is.
 */
final class EquipmentFixtures extends Fixture
{
    public const string BARBELL = 'equipment-barbell';
    public const string DUMBBELL = 'equipment-dumbbell';
    public const string BENCH = 'equipment-bench';
    public const string TREADMILL = 'equipment-treadmill';

    private const array WITH_WEIGHT = [
        'Barbell', 'Dumbbell', 'Kettlebell', 'EZ bar', 'Trap bar', 'Weight plate', 'Cable machine',
        'Smith machine', 'Leg press machine', 'Medicine ball', 'Weighted vest', 'Sandbag',
        'Lat pulldown machine', 'Seated row machine', 'T-bar row machine', 'Chest press machine',
        'Pec deck', 'Shoulder press machine', 'Leg extension machine', 'Leg curl machine',
        'Hack squat machine', 'Calf raise machine', 'Hip thrust machine', 'Hip abductor machine',
        'Hip adductor machine',
        // The weight is a counterweight taken off the body's own, but it is still a load to log.
        'Assisted pull-up machine',
        'Preacher curl machine', 'Ab crunch machine',
    ];

    private const array WITHOUT_WEIGHT = [
        'Bench', 'Incline bench', 'Pull-up bar', 'Dip bars', 'Jump rope',
        // A resistance, but not one measured in kilograms.
        'Resistance band',
        'Suspension trainer (TRX)', 'Plyo box', 'Ab wheel', 'Foam roller', 'Gym ball', 'Roman chair',
    ];

    private const array WITH_DISTANCE = ['Rowing machine', 'Stationary bike', 'Treadmill'];

    private const array REFERENCES = [
        'Barbell' => self::BARBELL,
        'Dumbbell' => self::DUMBBELL,
        'Bench' => self::BENCH,
        'Treadmill' => self::TREADMILL,
    ];

    public function __construct(private readonly EquipmentPersisterGateway $equipmentPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::WITH_WEIGHT as $name) {
            $this->create($name, true, false);
        }
        foreach (self::WITHOUT_WEIGHT as $name) {
            $this->create($name, false, false);
        }
        foreach (self::WITH_DISTANCE as $name) {
            $this->create($name, false, true);
        }
    }

    private function create(string $name, bool $hasWeight, bool $hasDistance): void
    {
        $equipment = new EquipmentDataModel();
        $equipment->name = $name;
        $equipment->hasWeight = $hasWeight;
        $equipment->hasDistance = $hasDistance;

        $this->equipmentPersisterGateway->create($equipment);

        if (true === isset(self::REFERENCES[$name])) {
            $this->addReference(self::REFERENCES[$name], $equipment);
        }
    }
}
