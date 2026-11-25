<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Gamification;

use App\Domain\DTO\Output\Gamification\ProgressDataOutput;
use App\Domain\DTO\Output\Gamification\StatDataOutput;
use App\Domain\DTO\Output\Gamification\StatGroupDataOutput;
use App\Domain\DTO\Output\Gamification\StatusDataOutput;
use App\Domain\Gamification\LevelCurve;
use App\Domain\Registry\Gamification\StatRegistry;

final readonly class StatusOutputFactory
{
    public function __construct(
        private LevelCurve $levelCurve,
    ) {
    }

    /** @param array<string, int> $experienceByStat a stat absent has none */
    public function buildOne(array $experienceByStat): StatusDataOutput
    {
        $output = new StatusDataOutput();
        $total = 0;

        foreach (StatRegistry::GROUPS as $group => $stats) {
            $groupOutput = new StatGroupDataOutput();
            $groupOutput->group = $group;
            foreach ($stats as $stat) {
                $experience = $experienceByStat[$stat] ?? 0;
                $total += $experience;

                $statOutput = new StatDataOutput();
                $statOutput->stat = $stat;
                $statOutput->progress = $this->progressOf($experience);
                $groupOutput->stats[] = $statOutput;
            }
            $output->groups[] = $groupOutput;
        }

        $output->progress = $this->progressOf($total);

        return $output;
    }

    private function progressOf(int $experience): ProgressDataOutput
    {
        $level = $this->levelCurve->levelFor($experience);

        $progress = new ProgressDataOutput();
        $progress->level = $level;
        $progress->experience = $experience;
        $progress->experienceIntoLevel = $experience - $this->levelCurve->experienceToReach($level);
        $progress->experienceForNextLevel = $this->levelCurve->experienceForNext($level);

        return $progress;
    }
}
