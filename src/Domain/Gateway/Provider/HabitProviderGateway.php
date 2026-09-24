<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HabitDataModel;

interface HabitProviderGateway
{
    public function findOneById(int $id): ?HabitDataModel;

    /**
     * The catalogue for the back-office, by name. `$isActive` narrows it: true for the offered
     * ones, false for the retired ones, null for both.
     *
     * @return list<HabitDataModel>
     */
    public function findAllForAdminList(?bool $isActive): array;

    /**
     * The catalogue people can subscribe to: the active habits, by name.
     *
     * @return list<HabitDataModel>
     */
    public function findAllActive(): array;
}
