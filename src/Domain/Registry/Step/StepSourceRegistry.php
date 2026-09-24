<?php

declare(strict_types=1);

namespace App\Domain\Registry\Step;

/**
 * Where a day's step count came from.
 *
 * A fixed vocabulary, not reference data: it names the writers the application knows about, so it
 * lives here rather than in a table an administrator maintains. Today the only writer is the
 * person themselves; `DEVICE` is declared ahead of the mobile sync that will send a day's total
 * automatically, so the column already has the value it will need and nothing has to migrate to
 * add it.
 */
interface StepSourceRegistry
{
    /** Typed in by the person, on the widget. */
    public const string MANUAL = 'manual';

    /** Pushed by a mobile app — ours or one we connect to. Not produced yet. */
    public const string DEVICE = 'device';
}
