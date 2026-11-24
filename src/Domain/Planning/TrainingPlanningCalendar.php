<?php

declare(strict_types=1);

namespace App\Domain\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Tracking\DayClock;
use DateTimeImmutable;

/**
 * The slots training entries give over some days, and where each stands. A slot is kept by a
 * workout of its routine started that day, wherever it was started from; one workout keeps one
 * slot, the earliest workout the earliest slot. A slot not kept is to come until its day is over,
 * then missed. Nothing of this is stored: it is read from the workouts each time.
 */
final readonly class TrainingPlanningCalendar
{
    private const string DAY = 'Y-m-d';

    public function __construct(
        private PlanningRule $planningRule,
        private DayClock $clock,
    ) {
    }

    /**
     * @param list<TrainingPlanningEntryDataModel> $entries  in the order slots of one day are kept in
     * @param list<WorkoutDataModel>               $workouts the owner's workouts started from a routine over those days, the earliest first
     * @param DateTimeImmutable                    $from     the first day, at midnight in the zone days are counted in
     * @param DateTimeImmutable                    $to       the last day, likewise
     *
     * @return list<array{entry: TrainingPlanningEntryDataModel, day: DateTimeImmutable, state: string, workout: WorkoutDataModel|null}>
     *                                                                                                                                   by day, then in the order of `$entries`
     */
    public function slotsBetween(array $entries, array $workouts, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $unclaimed = $this->workoutsByRoutineAndDay($workouts);
        $today = $this->clock->today()->format(self::DAY);

        $slots = [];
        foreach ($entries as $order => $entry) {
            foreach ($this->planningRule->datesOf($entry, $from, $to) as $day) {
                $slots[] = ['entry' => $entry, 'day' => $day, 'order' => $order];
            }
        }
        usort($slots, static fn (array $a, array $b): int => [$a['day']->format(self::DAY), $a['order']] <=> [$b['day']->format(self::DAY), $b['order']]);

        $result = [];
        foreach ($slots as $slot) {
            $day = $slot['day']->format(self::DAY);
            $key = $slot['entry']->routine->id.'@'.$day;
            $workout = [] === ($unclaimed[$key] ?? []) ? null : array_shift($unclaimed[$key]);

            $result[] = [
                'entry' => $slot['entry'],
                'day' => $slot['day'],
                'state' => match (true) {
                    null !== $workout => PlanningOccurrenceStateRegistry::KEPT,
                    $day < $today => PlanningOccurrenceStateRegistry::MISSED,
                    default => PlanningOccurrenceStateRegistry::TO_COME,
                },
                'workout' => $workout,
            ];
        }

        return $result;
    }

    /**
     * @param list<WorkoutDataModel> $workouts
     *
     * @return array<string, list<WorkoutDataModel>> by "<routine id>@<day>", each the earliest first
     */
    private function workoutsByRoutineAndDay(array $workouts): array
    {
        $byKey = [];
        foreach ($workouts as $workout) {
            if (null === $workout->routine) {
                continue;
            }
            $key = $workout->routine->id.'@'.$this->clock->inDisplayZone($workout->startedAt)->format(self::DAY);
            $byKey[$key][] = $workout;
        }

        return $byKey;
    }
}
