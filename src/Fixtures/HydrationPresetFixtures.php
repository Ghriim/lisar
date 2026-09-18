<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\Gateway\Persister\HydrationPresetPersisterGateway;
use App\Domain\Registry\Hydration\HydrationIconRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class HydrationPresetFixtures extends Fixture
{
    public const string GLASS = 'hydration-preset-glass';
    public const string MUG = 'hydration-preset-mug';
    public const string BOTTLE = 'hydration-preset-bottle';

    public function __construct(private readonly HydrationPresetPersisterGateway $persisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addReference(self::MUG, $this->createPreset(HydrationIconRegistry::MUG, 150));
        $this->addReference(self::GLASS, $this->createPreset(HydrationIconRegistry::GLASS, 250));
        $this->addReference(self::BOTTLE, $this->createPreset(HydrationIconRegistry::BOTTLE, 500));
    }

    private function createPreset(string $icon, int $volumeInMillilitres): HydrationPresetDataModel
    {
        $preset = new HydrationPresetDataModel();
        $preset->icon = $icon;
        $preset->volumeInMillilitres = $volumeInMillilitres;

        return $this->persisterGateway->create($preset);
    }
}
