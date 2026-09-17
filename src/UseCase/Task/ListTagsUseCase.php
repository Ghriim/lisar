<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\TagProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The tags an account has already used, so a front end can offer them back rather than let the
 * same word be typed three different ways.
 *
 * They are returned as plain labels: a tag is a word to the person using it, and there is nothing
 * else to say about one.
 */
final readonly class ListTagsUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private TagProviderGateway $tagProviderGateway,
    ) {
    }

    /**
     * @return list<string>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $labels = [];
        foreach ($this->tagProviderGateway->findAllForOwner($owner) as $tag) {
            $labels[] = $tag->label;
        }

        return $labels;
    }
}
