<?php

declare(strict_types=1);

namespace App\Fixtures\Habits;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Gateway\Persister\Habits\HabitPersisterGateway;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Fixtures\Category\CategoryFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The habit catalogue: two manual habits, two fed by a tracker, and one retired — enough to seed
 * every shape the panel and the back-office have to draw.
 */
final class HabitFixtures extends Fixture implements DependentFixtureInterface
{
    public const string READING = 'habit-reading';
    public const string MEDITATE = 'habit-meditate';
    public const string WALK = 'habit-walk';
    public const string DRINK = 'habit-drink';
    public const string RETIRED = 'habit-retired';

    public function __construct(private readonly HabitPersisterGateway $habitPersisterGateway)
    {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // One habit in a common category, so that category is held by something besides tasks.
        $reading = $this->buildActive('Lire');
        $reading->category = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);
        $this->addReference(self::READING, $this->habitPersisterGateway->create($reading));
        $this->addReference(self::MEDITATE, $this->manual('Méditer'));
        $this->addReference(self::WALK, $this->tracker('Marcher 5000 pas', HabitTrackerRegistry::STEPS, 5000));
        $this->addReference(self::DRINK, $this->tracker('Boire 1,5 L', HabitTrackerRegistry::HYDRATION, 1500));
        $this->addReference(self::RETIRED, $this->retired('Ancienne habitude'));
    }

    private function manual(string $name): HabitDataModel
    {
        return $this->create($name, true);
    }

    private function retired(string $name): HabitDataModel
    {
        return $this->create($name, false);
    }

    private function tracker(string $name, string $trackerKind, int $threshold): HabitDataModel
    {
        $habit = $this->buildActive($name);
        $habit->sourceKind = HabitSourceRegistry::TRACKER;
        $habit->trackerKind = $trackerKind;
        $habit->trackerThreshold = $threshold;

        return $this->habitPersisterGateway->create($habit);
    }

    private function create(string $name, bool $isActive): HabitDataModel
    {
        $habit = $this->buildActive($name);
        $habit->isActive = $isActive;

        return $this->habitPersisterGateway->create($habit);
    }

    private function buildActive(string $name): HabitDataModel
    {
        $habit = new HabitDataModel();
        $habit->name = $name;
        $habit->sourceKind = HabitSourceRegistry::MANUAL;
        $habit->isActive = true;

        return $habit;
    }
}
