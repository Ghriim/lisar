<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A quantity logged in one tap. Managed in the back-office, offered to everyone.
 *
 * It carries no label: the icon and the volume say it, and a word would have to be translated by
 * each front end anyway.
 */
#[ORM\Table(name: 'hydration_preset')]
#[ORM\Entity]
class HydrationPresetDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    /** One of HydrationIconRegistry. */
    #[ORM\Column(length: 32)]
    public string $icon;

    #[ORM\Column]
    public int $volumeInMillilitres;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
