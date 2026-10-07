<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;

/**
 * The default set type is the one a set logged without one takes: retiring or deleting it would
 * leave such a set with nothing. It is given to another type first.
 */
final readonly class SetTypeNotDefaultConstraint
{
    public const string IS_THE_DEFAULT = 'set_type_is_the_default';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(SetTypeDataModel $setType, array $violations = []): array
    {
        if (true === $setType->isDefaultType) {
            $violations['id'][] = self::IS_THE_DEFAULT;
        }

        return $violations;
    }
}
