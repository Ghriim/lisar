<?php

declare(strict_types=1);

namespace App\Fixtures\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The kinds of set, all active. « Travail », the ordinary working set, is the default: a set
 * logged without a type takes it. Every type counts for personal bests but the warm-up.
 */
final class SetTypeFixtures extends Fixture
{
    public const string WORKING = 'set-type-working';
    public const string WARM_UP = 'set-type-warm-up';
    public const string DROPSET = 'set-type-dropset';
    public const string FAILURE = 'set-type-failure';
    public const string BACK_OFF = 'set-type-back-off';

    private const array SEEDED = [
        self::WORKING => ['Travail', SetTypeColourRegistry::BLUE],
        self::WARM_UP => ['Échauffement', SetTypeColourRegistry::ORANGE],
        self::DROPSET => ['Dropset', SetTypeColourRegistry::PURPLE],
        self::FAILURE => ['Échec', SetTypeColourRegistry::RED],
        self::BACK_OFF => ['Back-off', SetTypeColourRegistry::TEAL],
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
            $setType->isDefaultType = self::WORKING === $reference;
            $setType->countsForPersonalBests = self::WARM_UP !== $reference;

            $this->setTypePersisterGateway->create($setType);
            $this->addReference($reference, $setType);
        }
    }
}
