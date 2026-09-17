<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\DTO\Output\Admin\UserCommentDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class UserCommentOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param UserCommentDataModel[] $comments
     *
     * @return list<UserCommentDataOutput>
     */
    public function buildMany(array $comments): array
    {
        $outputs = [];
        foreach ($comments as $comment) {
            $outputs[] = $this->buildOne($comment);
        }

        return $outputs;
    }

    public function buildOne(UserCommentDataModel $comment): UserCommentDataOutput
    {
        return $this->mapper->map($comment, UserCommentDataOutput::class);
    }
}
