<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\OutputFactory\UserOutputFactory;
use App\Domain\Registry\User\UserRoleRegistry;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;

use function sprintf;

final class UserOutputFactoryTest extends TestCase
{
    private UserOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UserOutputFactory(new ObjectMapper());
    }

    public function testItBuildsOneOutput(): void
    {
        $createdAt = new DateTimeImmutable('2026-09-17T14:32:05+02:00');

        $output = $this->factory->buildOne($this->buildUser('alice', $createdAt));

        self::assertSame('alice', $output->username);
        self::assertSame('alice@lisar.test', $output->email);
        self::assertTrue($output->isActive);
        self::assertSame(UserRoleRegistry::USER, $output->role);
        self::assertSame($createdAt->format(DateDataTransformer::FORMAT), $output->createdAt);
        self::assertNull($output->lastSignedInAt);
    }

    public function testItBuildsManyOutputs(): void
    {
        $outputs = $this->factory->buildMany([$this->buildUser('alice'), $this->buildUser('bob')]);

        self::assertCount(2, $outputs);
        self::assertSame('alice', $outputs[0]->username);
        self::assertSame('bob', $outputs[1]->username);
    }

    public function testItBuildsAPaginatedList(): void
    {
        $output = $this->factory->buildPaginated([$this->buildUser('alice')], total: 42, page: 2, perPage: 10);

        self::assertCount(1, $output->items);
        self::assertSame('alice', $output->items[0]->username);
        self::assertSame(42, $output->total);
        self::assertSame(2, $output->page);
        self::assertSame(10, $output->perPage);
    }

    private function buildUser(string $username, ?DateTimeImmutable $createdAt = null): UserDataModel
    {
        $user = new UserDataModel();
        $user->id = 1;
        $user->username = $username;
        $user->email = sprintf('%s@lisar.test', $username);
        $user->createdAt = $createdAt;

        return $user;
    }
}
