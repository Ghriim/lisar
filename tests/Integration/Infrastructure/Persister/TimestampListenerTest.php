<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persister;

use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\UseCase\Training\WorkoutTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** What the listener stamps reaches the database, rows persisted through a cascade included. */
final class TimestampListenerTest extends KernelTestCase
{
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** A copy is persisted from its root: its blocks, movements and sets come by cascade. */
    public function testEveryRowOfACopyIsStamped(): void
    {
        $copy = $this->copy(WorkoutFixtures::ALICE_FINISHED)->workout;

        // Read back from the database, not from what the use case held.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $workout = self::getContainer()->get(WorkoutProviderGateway::class)->findOneById($copy->id);
        self::assertNotNull($workout);

        $rows = [$workout];
        foreach ($workout->blocks as $block) {
            $rows[] = $block;
            foreach ($block->exercises as $exercise) {
                $rows[] = $exercise;
                foreach ($exercise->sets as $set) {
                    $rows[] = $set;
                }
            }
        }

        self::assertCount(14, $rows);
        foreach ($rows as $row) {
            self::assertNotNull($row->createdAt, $row::class);
            self::assertEquals($row->createdAt, $row->updatedAt, $row::class);
        }
    }
}
