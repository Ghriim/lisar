<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Persister\UserCommentPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserCommentFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ON_BOB = 'user-comment-on-bob';

    public function __construct(private readonly UserCommentPersisterGateway $userCommentPersisterGateway)
    {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $comment = new UserCommentDataModel();
        $comment->user = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $comment->author = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        $comment->body = 'Deactivated after a support request. Ask before reactivating.';

        $this->userCommentPersisterGateway->create($comment);

        $this->addReference(self::ON_BOB, $comment);
    }
}
