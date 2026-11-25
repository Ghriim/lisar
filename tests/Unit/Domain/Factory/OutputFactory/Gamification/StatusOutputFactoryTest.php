<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Gamification;

use App\Domain\DTO\Output\Gamification\ProgressDataOutput;
use App\Domain\DTO\Output\Gamification\StatusDataOutput;
use App\Domain\Factory\OutputFactory\Gamification\StatusOutputFactory;
use App\Domain\Gamification\LevelCurve;
use App\Domain\Registry\Gamification\StatRegistry;
use PHPUnit\Framework\TestCase;

final class StatusOutputFactoryTest extends TestCase
{
    /** Every stat, in its group and order, a stat with no gain at level 1; the global level on the sum. */
    public function testItBuildsTheStatusFromTheExperienceByStat(): void
    {
        $status = (new StatusOutputFactory(new LevelCurve()))->buildOne([
            StatRegistry::STRENGTH => 320,
            StatRegistry::ENDURANCE => 60,
            StatRegistry::INTELLIGENCE => 40,
        ]);

        self::assertSame([3, 420, 170, 450], $this->progress($status->progress));
        self::assertSame(['physical', 'mental'], array_map(static fn ($group) => $group->group, $status->groups));
        self::assertSame(
            [
                StatRegistry::STRENGTH => [3, 320, 70, 450],
                StatRegistry::ENDURANCE => [2, 60, 10, 200],
                StatRegistry::AGILITY => [1, 0, 0, 50],
                StatRegistry::INTELLIGENCE => [1, 40, 40, 50],
                StatRegistry::SPIRIT => [1, 0, 0, 50],
                StatRegistry::WILLPOWER => [1, 0, 0, 50],
            ],
            $this->stats($status),
        );
    }

    /** @return array<string, list<int>> */
    private function stats(StatusDataOutput $status): array
    {
        $stats = [];
        foreach ($status->groups as $group) {
            foreach ($group->stats as $stat) {
                $stats[$stat->stat] = $this->progress($stat->progress);
            }
        }

        return $stats;
    }

    /** @return list<int> */
    private function progress(ProgressDataOutput $progress): array
    {
        return [$progress->level, $progress->experience, $progress->experienceIntoLevel, $progress->experienceForNextLevel];
    }
}
