# Website · Steps

A widget on the quest log, beside hydration, weight and sleep: the day's steps against the day's
goal, glanced at rather than opened.

## The widget

A footprint, the count so far and the day's goal, and a progress bar between them. One icon opens
the window where the number is set: a pencil to correct a count already recorded, a plus to record
the first of the day.

That is all it shows. Like the others, the point of the widget is to be glanceable and to stay out
of the way of the quest log it sits next to.

## The window

Opened from the widget, it holds one field: **the day's step count**. It opens on the number
already recorded today, so correcting it is nudging a value rather than retyping it; on a day
nothing has been recorded yet, it opens empty. Saving records the total for the day in progress.

## The rules

- **A count is a running total, not an increment.** The field is the whole day's steps, set to
  what a watch or a phone says — not "add 2000 to today". Saving again **overwrites** the day's
  total; it never adds to it. This is what lets the same gesture serve a manual entry now and an
  automatic push from a mobile app later: both send the day's total, and a total that was summed
  would double.
- **The current day, and nothing else.** No count can be recorded for yesterday, and no past day
  can be edited from here. What is missed is missed. The back end never names a day: whatever day a
  long-open page thinks it is, the save lands on the one in progress.
- **A day starts at midnight in `Europe/Paris`**, for everyone, whatever their own clock says.
  This is the placeholder every tracker shares: the profile stores no timezone yet.
- **The goal is 10000 steps**, the same for everyone. A personal goal is planned and deliberately
  absent for now.
- **The day's goal is frozen into the day itself**, the first time a count is recorded. Raising the
  goal from 10000 to 12000 must not retroactively turn a day that was reached into a day that was
  missed. Nothing reads this yet — the statistics that will are worth not having to reconstruct.
- **Zero is a real count**, a day recorded as having no steps. It is not the same as a day nothing
  was recorded on: the widget shows a target to fill until the first save, and the recorded total
  after it — zero included.

## Where the count comes from

Every count carries a **source**, so the day the steps arrive on their own can be told apart from
the day they were typed. Today there is one source, `manual`: the person, on this widget. A second,
`device`, is declared for the mobile sync to come — our own app, or one we connect to (Health
Connect, Apple Health, a watch) — and the column already holds it, so that path adds a writer, not
a migration. Nothing produces `device` yet.

## Not in v1

- any view of the past: history, averages, streaks, charts. A statistics domain will come, and it
  will read what is being recorded now.
- a personal goal, and a dynamic one.
- the automatic sync itself: the model does not preclude it, but no ingestion route exists yet.
- reminders and notifications.

## Midnight, with the page still open

As with the other trackers, **no request ever names a day**: a count is recorded to the day in
progress as the server counts it, so a page showing yesterday's total still writes to today. The
page is what goes stale, and the whole page — it already watches the server-reported day and
reloads when it turns (see hydration for the mechanism). The step widget inherits that watch; it
does not add its own.
