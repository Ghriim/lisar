<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\User;

use App\Domain\DTO\DataModel\User\UserCommentDataModel;

interface UserCommentPersisterGateway
{
    public function create(UserCommentDataModel $comment): UserCommentDataModel;
}
