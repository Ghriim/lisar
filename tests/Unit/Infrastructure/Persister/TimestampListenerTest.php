<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persister;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Infrastructure\Persister\TimestampListener;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Clock\MockClock;

final class TimestampListenerTest extends TestCase
{
    private MockClock $clock;
    private TimestampListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new MockClock(new DateTimeImmutable('2026-10-16 09:00:00'));
        $this->listener = new TimestampListener($this->clock);
    }

    public function testARowPersistedIsStampedCreatedAndUpdatedAtOnce(): void
    {
        $dataModel = new MuscleGroupDataModel();

        $this->listener->prePersist(new PrePersistEventArgs($dataModel, self::createStub(EntityManagerInterface::class)));

        self::assertEquals(new DateTimeImmutable('2026-10-16 09:00:00'), $dataModel->createdAt);
        self::assertEquals($dataModel->createdAt, $dataModel->updatedAt);
    }

    public function testARowUpdatedMovesItsUpdatedAtOnly(): void
    {
        $dataModel = new MuscleGroupDataModel();
        $this->listener->prePersist(new PrePersistEventArgs($dataModel, self::createStub(EntityManagerInterface::class)));

        $this->clock->sleep(3600);
        $changeSet = ['name' => ['Chest', 'Pecs']];
        $this->listener->preUpdate(new PreUpdateEventArgs($dataModel, self::createStub(EntityManagerInterface::class), $changeSet));

        self::assertEquals(new DateTimeImmutable('2026-10-16 09:00:00'), $dataModel->createdAt);
        self::assertEquals(new DateTimeImmutable('2026-10-16 10:00:00'), $dataModel->updatedAt);
    }

    /** Only data models are its business. */
    public function testAnythingButADataModelIsLeftAlone(): void
    {
        $object = new stdClass();

        $this->listener->prePersist(new PrePersistEventArgs($object, self::createStub(EntityManagerInterface::class)));

        self::assertSame([], get_object_vars($object));
    }
}
