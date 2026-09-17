<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\PaginatedListDataOutput;
use App\Domain\DTO\Output\User\UserDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class UserOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param UserDataModel[] $users
     *
     * @return list<UserDataOutput>
     */
    public function buildMany(array $users): array
    {
        $outputs = [];
        foreach ($users as $user) {
            $outputs[] = $this->buildOne($user);
        }

        return $outputs;
    }

    public function buildOne(UserDataModel $user): UserDataOutput
    {
        return $this->mapper->map($user, UserDataOutput::class);
    }

    /**
     * @param UserDataModel[] $users
     *
     * @return PaginatedListDataOutput<UserDataOutput>
     */
    public function buildPaginated(array $users, int $total, int $page, int $perPage): PaginatedListDataOutput
    {
        /** @var PaginatedListDataOutput<UserDataOutput> $output */
        $output = new PaginatedListDataOutput();
        $output->items = $this->buildMany($users);
        $output->total = $total;
        $output->page = $page;
        $output->perPage = $perPage;

        return $output;
    }
}
