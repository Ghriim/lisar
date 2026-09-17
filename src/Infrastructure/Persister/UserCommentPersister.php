<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\Gateway\Persister\UserCommentPersisterGateway;

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
