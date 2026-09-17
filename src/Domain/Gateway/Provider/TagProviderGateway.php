<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface TagProviderGateway
{
    /** @return list<TagDataModel> */
    public function findAllForOwner(UserDataModel $owner): array;

    /**
     * The account's tags that match these labels, so a use case can tell which ones it still
     * has to create.
     *
     * @param list<string> $labels
     *
     * @return list<TagDataModel>
     */
    public function findAllByLabelsForOwner(UserDataModel $owner, array $labels): array;
}
