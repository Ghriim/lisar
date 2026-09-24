<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Step;

/**
 * The day in progress: its goal, and the count so far if one has been recorded.
 *
 * `day` and `goalInSteps` are always present — a day always has a goal, whether or not anything
 * has been walked against it. `countInSteps` and `source` are null until the first save: that
 * null is how the widget knows to offer "record" rather than "correct", and it must not be
 * confused with a count of zero, which is a day someone recorded as having no steps.
 */
final class StepDayDataOutput
{
    /** The calendar day this count belongs to, YYYY-MM-DD. */
    public string $day;

    public int $goalInSteps;

    public ?int $countInSteps = null;

    /** Which writer set the count — see Domain\Registry\Step\StepSourceRegistry. */
    public ?string $source = null;
}
