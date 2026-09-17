<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\OutputFactory\UserCommentOutputFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class UserCommentOutputFactoryTest extends TestCase
{
    private UserCommentOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // #[Map(source: 'author.username')] walks a path, which needs a property accessor —
        // the one the container wires into the mapper in the application.
        $this->factory = new UserCommentOutputFactory(
            new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor()),
        );
    }

    public function testItFlattensTheAuthorIntoTheOutput(): void
    {
        $output = $this->factory->buildOne($this->buildComment('Called support back.'));

        self::assertSame('Called support back.', $output->body);
        self::assertSame(7, $output->authorId);
        self::assertSame('admin', $output->authorUsername);
        self::assertSame('2026-09-17T10:00:00+00:00', $output->createdAt);
    }

    public function testItBuildsManyOutputs(): void
    {
        $outputs = $this->factory->buildMany([$this->buildComment('first'), $this->buildComment('second')]);

        self::assertCount(2, $outputs);
        self::assertSame('first', $outputs[0]->body);
        self::assertSame('second', $outputs[1]->body);
    }

    private function buildComment(string $body): UserCommentDataModel
    {
        $author = new UserDataModel();
        $author->id = 7;
        $author->username = 'admin';
        $author->email = 'admin@lisar.test';

        $comment = new UserCommentDataModel();
        $comment->id = 1;
        $comment->body = $body;
        $comment->author = $author;
        $comment->user = new UserDataModel();
        $comment->createdAt = new DateTimeImmutable('2026-09-17T10:00:00+00:00');

        return $comment;
    }
}
