<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\User;

use App\Domain\DTO\DataModel\User\UserCommentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface UserCommentProviderGateway
{
    /**
     * The whole thread of an account, newest first, with its authors.
     *
     * @return list<UserCommentDataModel>
     */
    public function findAllForUser(UserDataModel $user): array;
}
