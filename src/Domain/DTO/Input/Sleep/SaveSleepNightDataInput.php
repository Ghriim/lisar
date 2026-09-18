<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Sleep;

use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Sleep\SleepMoodRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Two times of day and, if one feels like it, a face.
 *
 * No date: someone who has just woken up should not have to pick one, and there is only one they
 * could mean. Which day each time falls on is worked out by Domain\Tracking\SleepWindow.
 */
final readonly class SaveSleepNightDataInput implements DataInputInterface
{
    public function __construct(
        // Regex and NotBlank divide the work: every constraint but NotBlank treats an empty
        // string as valid, so an empty field reports "required" and a malformed one "invalid",
        // never both.
        #[Assert\NotBlank(message: 'bedtime_required')]
        #[Assert\Regex(pattern: TimeDataTransformer::PATTERN, message: 'bedtime_invalid')]
        public string $bedtime,

        #[Assert\NotBlank(message: 'wake_up_time_required')]
        #[Assert\Regex(pattern: TimeDataTransformer::PATTERN, message: 'wake_up_time_invalid')]
        public string $wakeUpTime,

        // Range skips null, so leaving oneself unrated is simply not answering.
        #[Assert\Range(
            min: SleepMoodRegistry::WORST,
            max: SleepMoodRegistry::BEST,
            notInRangeMessage: 'mood_rating_invalid',
        )]
        public ?int $moodRating = null,
    ) {
    }
}
