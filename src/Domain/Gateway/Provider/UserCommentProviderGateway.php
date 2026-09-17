<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface UserCommentProviderGateway
{
    /**
     * The whole thread of an account, newest first, with its authors.
     *
     * @return list<UserCommentDataModel>
     */
    public function findAllForUser(UserDataModel $user): array;
}
