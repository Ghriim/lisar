# Website · Weight

A widget on the quest log, beside the hydration one. Weighing yourself is a once-a-day gesture
that takes a single number: it does not deserve a screen, and a screen would only make it a
chore.

## The widget

The **last known weight and the day it was recorded** — not today's, the last one. Someone who
skipped three days still sees where they stand; showing nothing until they step on the scale
would tell them the least on the day they most want to know.

Beside it, one icon opens the window where the number is written.

## The window

One field, in kilograms, and the day it will be recorded for — today, never anything else.

The field opens **pre-filled with the last known weight**, whatever day it came from. A weight
moves by hundreds of grams, so the previous number is almost the next one, and retyping it in
full is work for nothing. This is the one place the rule "nothing is preselected" does not hold,
and deliberately: a weight is a correction of the last one, not a choice among options.

If today's weight is already recorded, the window says so and writes over it.

## The rules

- **One weight per day.** Weighing yourself twice in a day is the same measurement taken twice,
  not two facts; the second one is the one that counts.
- **The current day, and nothing else.** No weight can be recorded for yesterday, and no past day
  can be edited. What is missed is missed — the same rule as hydration, for the same reason.
- **A weight can be corrected, never deleted.** Correcting is how a typo is undone: recording
  again simply replaces today's number. There is nothing to delete, because a deleted weight
  would mean "I did not weigh myself", and that is said by not recording one.
- **Recorded with a date and a time.** The day is what the weight belongs to; the time is when
  the person stepped on the scale, and it matters — a morning weight and an evening weight are
  not comparable. Nothing reads the time yet; the statistics that will are worth not having to
  invent it later.
- **The time is set once**, when the day's weight is first recorded, and a correction does not
  move it. Fixing a typo at noon does not mean the person weighed themselves at noon.
- **A day starts at midnight in `Europe/Paris`**, for everyone. The same placeholder as
  hydration, and the same decision to reopen the day the app has users elsewhere.
- **Kilograms, two decimals.** 72.40, not 72.4 and not 72. Other units come with the profile that
  will carry them.
- **Between 20 and 400 kg.** Not a medical judgement — a typo filter. A missed decimal point
  turns 72.4 into 724 and would poison every average the statistics domain ever computes.

## Not in v1

- **any history**: the list of past weights, the curve, the averages, the variation since last
  week. Everything needed for them is being recorded — one row per day, with its time — and none
  of it is shown. This is the first thing the statistics domain will read.
- **a target weight**, and everything that follows from it: progress towards it, a projected date,
  reaching it.
- reminders

## Midnight, with the page still open

Nothing about the weight widget breaks: **no request names a day**, so a page opened yesterday
still records to today. And because the widget shows the last known weight with its date rather
than today's, the number on screen does not become a lie at midnight — it becomes yesterday's
weight, which is exactly what it says it is.

The page reloads all the same, because the hydration widget next to it does go stale. That check
belongs to the page, not to either widget — see `hydration.md`.
