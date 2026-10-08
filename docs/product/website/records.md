# Website · Records

`/records` — a person's personal bests, in one place: those of whole workouts, then movement by
movement. They are kept by the server as sets are ticked; nothing here is entered by hand.

## What counts

**Only a set ticked as done, of a [set type](../admin/set-types.md) that counts for records.**
Every type counts unless the back-office switched it off — the warm-up is off as seeded: a light
set on purpose is no record. A set logged but not ticked yet is still to do, and beats nothing.

A record is **strictly beaten**: a tie leaves it with whoever set it first. Nothing of nothing is
a record — an empty bar, a workout without a set that counts.

A **unilateral** movement is read like any other: its reps and loads as logged, a side's.

## Which records

They follow from what the [movement tracks](../admin/movements.md#what-a-set-records); no
movement is configured for them.

| The movement tracks | Beaten by one set | Beaten by one workout's sets of it, added up |
| --- | --- | --- |
| reps and a load | the heaviest load · **the heaviest load for at least 1, 3, 5, 8, 10, 12 reps** (1RM, 3RM…) · the estimated 1RM · the biggest set volume (reps × load) | the biggest volume |
| reps, no load | the most reps | the most reps |
| a duration, no distance | the longest duration | the longest total |
| a distance, no load | the longest distance | the longest total |
| a distance and a duration, no load | **the best time for 1 km, 5 km, 10 km, the half, the marathon, 50, 100 and 150 km** · the best pace | |
| a load and a distance | the heaviest load · **the longest distance for each load carried** | |

- **The estimated 1RM** is Epley's — load × (1 + reps / 30) — over sets of **10 reps at most**:
  beyond, the formula drifts. A single is its own load.
- **A time for a distance** counts every set at least that long, **brought back to the distance at
  its own pace**: 6 km in 30 min is 25:00 for the 5 km. A set shorter than the distance does not
  count for it.
- **The pace** counts only sets of **1 km at least**: a 100 m sprint would win it otherwise.

**Across movements**, a whole workout has three records of its own: the biggest volume, the most
sets that count, and **the longest workout, from its start to its finish** — only a finished
workout has one.

## When it moves

At once. Ticking a set that beats a record shows the record on the set straight away; unticking
it, correcting it down or removing it **hands the record back to whoever held it before**, and so
does abandoning or deleting the workout. A set type switched to count, or not to, rebuilds the
records of every set carrying it.

Each time a record goes up is kept: **a record is its whole progression**, the latest step being
the record itself, dated by the start of the workout that beat it.

## The page

- **« Records de séance »** first: the records of whole workouts, or « Aucun record pour
  l’instant » while there are none.
- **« Records par mouvement »** then: every movement on offer, grouped by family, each with how
  many records it holds or « Aucun record ». A movement retired since still shows while it holds
  some, chipped « Retiré ».
- A movement unfolds on its records — what each is, its value, the day it was beaten — and a
  record unfolds on the times it was beaten before, the latest first.
- **Reached on one record** — `/records#record-<movement>-<kind>-<tier>`, from a set that beat
  it — the page unfolds that record's movement and scrolls it into view. A record of whole
  workouts carries `seance` for its movement. The anchor names the record, not one step of it:
  a set whose record was beaten since leads to the record as it stands, its own step folded under.

Not paginated: a movement list and its records, read at once.

## Elsewhere

- **On a set** of a workout, a gold chip per record it beat, by its name alone: « 5RM ». On a
  finished workout the chip **opens this page on that record**; while the workout runs it leads
  nowhere, since the next tick may still move the record.
- **In the bilan** of a workout, « Records battus »: every record it beat, by one of its sets or as
  a whole.

No experience, no badge yet: they will hang on these rows.
