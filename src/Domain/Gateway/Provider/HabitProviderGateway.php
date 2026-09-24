<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HabitDataModel;

interface HabitProviderGateway
{
    public function findOneById(int $id): ?HabitDataModel;

    /**
     * The whole catalogue, active and retired alike, newest first — the back-office list.
     *
     * @return list<HabitDataModel>
     */
    public function findAllForAdminList(): array;

    /**
     * The catalogue people can subscribe to: the active habits, by name.
     *
     * @return list<HabitDataModel>
     */
    public function findAllActive(): array;
}
