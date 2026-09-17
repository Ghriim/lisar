<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Gateway\Persister\PriorityPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class PriorityFixtures extends Fixture
{
    public const string LOW = 'priority-low';
    public const string NORMAL = 'priority-normal';
    public const string HIGH = 'priority-high';

    public function __construct(private readonly PriorityPersisterGateway $priorityPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addReference(self::HIGH, $this->createPriority('High', 10, '#e5484d', false));
        // The one that applies to a task created without a priority.
        $this->addReference(self::NORMAL, $this->createPriority('Normal', 20, '#3e63dd', true));
        $this->addReference(self::LOW, $this->createPriority('Low', 30, '#889096', false));
    }

    private function createPriority(string $label, int $weight, string $colour, bool $isDefault): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->label = $label;
        $priority->weight = $weight;
        $priority->colour = $colour;
        $priority->isDefault = $isDefault;

        return $this->priorityPersisterGateway->create($priority);
    }
}
