<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\Gateway\Persister\HabitPersisterGateway;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The habit catalogue: two manual habits, two fed by a tracker, and one retired — enough to seed
 * every shape the panel and the back-office have to draw.
 */
final class HabitFixtures extends Fixture
{
    public const string READING = 'habit-reading';
    public const string MEDITATE = 'habit-meditate';
    public const string WALK = 'habit-walk';
    public const string DRINK = 'habit-drink';
    public const string RETIRED = 'habit-retired';

    public function __construct(private readonly HabitPersisterGateway $habitPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addReference(self::READING, $this->manual('Lire', HabitIconRegistry::BOOK));
        $this->addReference(self::MEDITATE, $this->manual('Méditer', HabitIconRegistry::MOON));
        $this->addReference(self::WALK, $this->tracker('Marcher 10 000 pas', HabitIconRegistry::RUN, HabitTrackerRegistry::STEPS, 10000));
        $this->addReference(self::DRINK, $this->tracker('Boire 1,5 L', HabitIconRegistry::DROPLET, HabitTrackerRegistry::HYDRATION, 1500));
        $this->addReference(self::RETIRED, $this->retired('Ancienne habitude', HabitIconRegistry::HEART));
    }

    private function manual(string $name, string $icon): HabitDataModel
    {
        return $this->create($name, $icon, true);
    }

    private function retired(string $name, string $icon): HabitDataModel
    {
        return $this->create($name, $icon, false);
    }

    private function tracker(string $name, string $icon, string $trackerKind, int $threshold): HabitDataModel
    {
        $habit = $this->buildActive($name, $icon);
        $habit->sourceKind = HabitSourceRegistry::TRACKER;
        $habit->trackerKind = $trackerKind;
        $habit->trackerThreshold = $threshold;

        return $this->habitPersisterGateway->create($habit);
    }

    private function create(string $name, string $icon, bool $isActive): HabitDataModel
    {
        $habit = $this->buildActive($name, $icon);
        $habit->isActive = $isActive;

        return $this->habitPersisterGateway->create($habit);
    }

    private function buildActive(string $name, string $icon): HabitDataModel
    {
        $habit = new HabitDataModel();
        $habit->name = $name;
        $habit->icon = $icon;
        $habit->sourceKind = HabitSourceRegistry::MANUAL;
        $habit->isActive = true;

        return $habit;
    }
}
