<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\UserCommentDataModel;

interface UserCommentPersisterGateway
{
    public function create(UserCommentDataModel $comment): UserCommentDataModel;
}
