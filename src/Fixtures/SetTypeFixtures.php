<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\Gateway\Persister\SetTypePersisterGateway;
use App\Domain\Registry\Workout\SetTypeColourRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The kinds of set worth marking, all active. No "working set" row: a set without a type is one.
 */
final class SetTypeFixtures extends Fixture
{
    public const string WARM_UP = 'set-type-warm-up';
    public const string DROPSET = 'set-type-dropset';
    public const string FAILURE = 'set-type-failure';
    public const string BACK_OFF = 'set-type-back-off';

    private const array SEEDED = [
        self::WARM_UP => ['Échauffement', SetTypeColourRegistry::ORANGE],
        self::DROPSET => ['Dropset', SetTypeColourRegistry::PURPLE],
        self::FAILURE => ['Échec', SetTypeColourRegistry::RED],
        self::BACK_OFF => ['Back-off', SetTypeColourRegistry::BLUE],
    ];

    public function __construct(private readonly SetTypePersisterGateway $setTypePersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::SEEDED as $reference => [$name, $colour]) {
            $setType = new SetTypeDataModel();
            $setType->name = $name;
            $setType->colour = $colour;

            $this->setTypePersisterGateway->create($setType);
            $this->addReference($reference, $setType);
        }
    }
}
