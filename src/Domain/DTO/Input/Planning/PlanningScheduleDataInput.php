<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * When a planning entry falls: the same on every kind of entry. Each field is checked for its own
 * shape here; which fields a rule needs, and how they hold together, is PlanningScheduleConstraint's.
 * A field the rule does not read is ignored, not refused.
 */
final readonly class PlanningScheduleDataInput implements DataInputInterface
{
    public const int MAX_WEEK_INTERVAL = 52;
    public const int MAX_DURATION_IN_MINUTES = 24 * 60;

    /**
     * @param list<int> $weekdays   ISO weekdays, 1 for Monday
     * @param list<int> $monthWeeks 1 to 5, or -1 for the last
     * @param list<int> $monthDays  1 to 31
     */
    public function __construct(
        #[Assert\NotBlank(message: 'rule_required')]
        #[Assert\Choice(choices: PlanningRuleRegistry::ALL, message: 'rule_unknown')]
        public string $rule = '',

        // Calendar days, "YYYY-MM-DD".
        #[Assert\NotBlank(message: 'start_date_required')]
        #[Assert\Date(message: 'start_date_invalid')]
        public string $startDate = '',

        #[Assert\Date(message: 'end_date_invalid')]
        public ?string $endDate = null,

        #[Assert\All([new Assert\Range(min: 1, max: 7, notInRangeMessage: 'weekday_invalid')])]
        public array $weekdays = [],

        #[Assert\Range(min: 1, max: self::MAX_WEEK_INTERVAL, notInRangeMessage: 'week_interval_invalid')]
        public int $weekInterval = 1,

        #[Assert\All([new Assert\Choice(choices: [1, 2, 3, 4, 5, PlanningRuleRegistry::LAST_WEEK], message: 'month_week_invalid')])]
        public array $monthWeeks = [],

        #[Assert\All([new Assert\Range(min: 1, max: 31, notInRangeMessage: 'month_day_invalid')])]
        public array $monthDays = [],

        // A time of day, "HH:MM". None: within the day.
        #[Assert\Regex(pattern: TimeDataTransformer::PATTERN, message: 'start_time_invalid')]
        public ?string $startTime = null,

        #[Assert\Range(min: 1, max: self::MAX_DURATION_IN_MINUTES, notInRangeMessage: 'duration_invalid')]
        public ?int $durationInMinutes = null,
    ) {
    }
}
