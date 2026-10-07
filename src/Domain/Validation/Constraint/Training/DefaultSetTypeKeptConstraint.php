<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;

/**
 * There is always exactly one default set type, because a set logged without one has to get
 * something. So the default is never *unset* — it is given to another type, which takes it away
 * from the one that had it — and it is never given to a retired type, which no new set may take.
 */
final readonly class DefaultSetTypeKeptConstraint
{
    public const string DEFAULT_REQUIRED = 'default_set_type_required';
    public const string DEFAULT_INACTIVE = 'default_set_type_inactive';

    /**
     * @param bool                        $isBecomingDefault what the caller is asking for
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(SetTypeDataModel $setType, bool $isBecomingDefault, array $violations = []): array
    {
        if (true === $setType->isDefaultType && false === $isBecomingDefault) {
            $violations['isDefaultType'][] = self::DEFAULT_REQUIRED;
        }

        if (false === $setType->isActive && true === $isBecomingDefault) {
            $violations['isDefaultType'][] = self::DEFAULT_INACTIVE;
        }

        return $violations;
    }
}
