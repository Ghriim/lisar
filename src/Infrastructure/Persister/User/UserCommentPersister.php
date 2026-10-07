<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\UserCommentDataModel;
use App\Domain\Gateway\Persister\User\UserCommentPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<UserCommentDataModel>
 */
final class UserCommentPersister extends AbstractBaseMysqlPersister implements UserCommentPersisterGateway
{
    public function create(UserCommentDataModel $comment): UserCommentDataModel
    {
        return $this->persistAndStampCreate($comment);
    }
}
