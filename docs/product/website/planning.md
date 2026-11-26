# Website · Planning

`/planning` is where a person lays out their weeks: **which routine on which day, which task to do
when, which days a habit counts**. Each of the three keeps its own page — the routines on
`/workouts`, the task list on `/todo`, the habits on the dashboard — and this page is the one place
where they are scheduled and seen side by side.

A planning entry is **a rule, not a list of things already made**. Planning « Push every Tuesday »
writes nothing into the workout history: the Tuesday is a slot, and the workout is created the day
it is started, its loads worked out from that day's records.

## A planning entry

A planning entry ties **one thing** to **a rule of dates**, and optionally **a time of day**. There
are three kinds of entry, one per domain, each kept apart from the others — which is what will let
the page filter by kind:

| Kind | Ties | See |
| --- | --- | --- |
| **training** | a routine of the person's | [A training entry](#a-training-entry) |
| **task** | a task model: title, description, category | [A task entry](#a-task-entry) |
| **habit** | a habit the person is subscribed to | [A habit entry](#a-habit-entry) |

Every kind carries the same schedule:

| Field | Note |
| --- | --- |
| rule | see [The rules of dates](#the-rules-of-dates) — **required**. **« Tous les jours » by default**, first in the list |
| start date | the first day the rule can fall on. **Today by default**, shown filled in |
| end date | optional: the last day the rule can fall on — an 8-week cycle, a season |
| start time | optional: without one, the entry holds **within the day** |
| duration | optional, **and only with a start time** |

One thing may carry several entries — « Push on Tuesdays at 18:00 » and « Push the first Saturday
of the month » — and several entries may fall on the same day, or overlap in time: the planning
shows them, it does not arbitrate. **One entry, one thing**: two routines on the same Tuesday are
two entries.

## The rules of dates

One engine, shared by the three kinds:

| Rule | Example |
| --- | --- |
| **once** | Saturday 28 November |
| **days of the week**, every week | Monday, Wednesday, Friday — every day is the seven of them |
| **days of the week, every N weeks** | Monday, every other week, counted from the start date |
| **N-th weekday of the month** | the 1st and 2nd Monday; the last Friday |
| **fixed day of the month** | the 15th; a month without that day (the 31st) is skipped |

**« Tous les jours »** is offered as a choice of its own, first in the list, and chosen from the
start. It is not a rule of its own: it is written as days of the week, all seven, every week — and
an entry on that rule is shown back as « Tous les jours » when it is changed.

**The days are listed with commas only**: « lundi, mercredi, vendredi », never « … et vendredi ».

No single date can be moved or skipped on its own: to change a date, the rule is changed.

**Changing an entry changes the dates from today on.** What already fell — a slot kept, a slot
missed, a task already created — stays as it was. **Deleting an entry** removes the dates to come
and keeps the past the same way: a workout started, a task created, a habit day kept.

**Weeks start on Monday, and every day and time is read in `Europe/Paris`**, as everywhere in the
app.

## The page

The page is titled `[ PLANNING ]`, above its card as on the dashboard, with **the plus** on the
right — « Planifier », the main button.

**The week**, Monday to Sunday, opens on the current one; arrows go to the previous and next, either
side of « Semaine du 23 novembre ». Seven columns, each with its day and date — written plainly,
not in spaced capitals; today's in the accent:

- what holds **within the day** sits at the head of its column;
- what has **a start time** sits on the hour grid, as tall as its duration — an entry without a
  duration is a mark at its hour. A card short on time still grows to show **its kind's icon and
  its name** (the routine's, the task's, the habit's): the name is never squeezed away to fit, it
  is cut with an ellipsis when the column is narrow.

Each date carries its kind's icon, its name and its state:

| Kind | States |
| --- | --- |
| training | **to come** · **kept** (a workout of that routine started that day) · **missed** (the day went by without one) |
| task | **to come** (no task made yet) · **open** (its task exists, not done — shown overdue once its day is past) · **done** |
| habit | **to come** · **kept** · **missed** — plus **kept, unplanned** for a habit kept on a day no entry asked for |

**The plus** beside the title creates an entry: it asks the kind — training, task, habit —
then what it ties, then the rule and the time. **The eye on a date** opens its entry — every field,
filled in or not — to change it or delete it after asking. Today's training slot not kept yet also
carries **« Démarrer »**.

**A date is ticked off from the week**, where there is something to tick:

- **a task date whose task exists** — today's, or one of a day gone by, overdue included — carries
  the check, « Terminer », and once done the undo, « Rouvrir ». A date still to come has no task
  yet, so nothing to tick. It is the same gesture as in the task list, and the list follows.
- **today's date of a habit kept by hand** carries the check, « Valider », and once kept the undo,
  « Décocher » — as on the habits panel, which follows. A habit is kept for the day in progress
  only, so its other days carry none; a habit its tracker keeps carries none either. A day's
  habits held within the day are ticked from the window their card opens.

Whatever the server refuses is said once, above the week.

The form asks the rule first, and then only the fields that rule reads: the days of the week and
« une semaine sur N » for a weekly rule, the days and their rank in the month for a monthly one,
the days of the month for the other. The rule opens on « Tous les jours » and the start date on
today; nothing else is chosen for the person. A duration is asked only once a start time is given.

Narrower than a laptop, the seven columns stack, one day under the other, each listing its dates
in order rather than on an hour grid.

The plus asks between the three kinds. A task entry's dates show **its category's icon** — the task list's
when it has none, or only a personal one — and the task's title; an open task of a day gone by shows as « En retard ». A habit entry's dates show **its
category's icon** — the habits' calendar mark when it has none — and the habit's name; a day kept unplanned shows as « Bonus », with no eye, since no entry asks
for it.

## A training entry

- **The slot is not a workout.** It becomes one only when someone starts it.
- **Today's slot offers « Démarrer »**, under the same rule as everywhere: only while no workout
  is in progress. It starts the workout exactly as the routine's own button does
  ([Starting a workout from a routine](routines.md#starting-a-workout-from-a-routine)).
- **A slot is kept by any workout of that routine started that day**, wherever it was started —
  from the slot, or from the « Routines » tab. One workout keeps one slot.
- **A slot the day went by without is missed**, and shown so. That is information, nothing more for
  now: the gamification will read it.
- A slot of a past day offers nothing to do. A routine deleted takes its entries with it.

## A task entry

A task entry makes **tasks from a model** it holds: a title, a description, a category. Subtasks
are not part of a model.

- **Recurring**: each date gets **its own task, created that day** (from midnight), with that date
  as its due date. Before the day, it shows only on the planning, as to come.
- **A task not done stays open**, overdue, as any task past its due date: the next date creates
  its own task beside it, and nothing is closed on the person's behalf. Three Mondays missed are
  three open tasks — the task list shows them overdue, each with its day.
- **Once**: the task is **created at once**, with the date as its due date, so it can be seen
  coming in the task list. Not done, it stays open and overdue.
- **The time is the planning's**: an entry with a start time places its dates on the hour grid;
  without one they sit within the day. The task made carries no time of day, like every task,
  and is otherwise ordinary: done by hand, edited, deleted.
- **Changing the entry** changes the dates to come: a task already made for a date still ahead —
  a single date's — and not done goes, and is made again from the new model and rule. Today's
  task and those before stay as they were, even when the rule no longer gives their date.
  **Deleting the entry** likewise deletes such a task to come, and keeps the others in the task
  list, no longer on the planning.
- A date gone by whose task was deleted by hand no longer shows; one still ahead shows as to
  come.
- **The tasks are made each night**, a few minutes past midnight: a job runs on the server and
  makes the day's task of every recurring entry falling on it. A night the job did not run makes
  nothing for that day, unless it is replayed by hand.

## A habit entry

- **The days a habit counts are the person's**: a habit entry belongs to their subscription, not
  to the catalogue.
- **A kept habit always has a planning.** Taking a habit on — or taking it back up after a drop —
  plans it **every day, from that day**, as one entry; the person changes it from there. **Its last
  entry cannot be deleted**: the delete is not offered, and the server refuses it
  (`last_habit_planning_entry`). To stop keeping it, one unsubscribes.
- **A run counts planned days only.** A day no entry asks for neither breaks the run nor extends it.
- **A habit can still be kept on an unplanned day**: the day is recorded as kept, shown as a bonus,
  and counts for nothing in the run.
- The seven circles of the [habits panel](habits.md#the-panel) show the planned days as they are,
  and the others dimmed. Its entries are created and changed here **and from the habit's own line
  on the panel**, through the same form.
- Unsubscribing takes the habit's entries with it; subscribing again starts from every day.
- **On the week, a day's habits held within the day share one card**, « 3/5 habitudes »: how many
  were kept (bonus days included) out of how many the day holds. Every habit is planned, most of
  them every day: one card each would bury the rest of the week. The card's border says how the
  day went — green when all were kept, red when one was missed, the accent otherwise — and its eye
  opens a window listing them **under their category**, as on the dashboard (flat while none has
  one), each with its state, **the days it counts** (« quotidien », « lundi, jeudi »), and its own
  eye on its entry. The entry opens in the same window,
  which changes what it holds rather than closing and opening again. **A habit planned
  at an hour keeps its own card** on the hour grid, like any timed entry.

## Navigation

« Planning » joins the main menu, after « Todo », with the calendar icon.

## Not here yet

Filtering the page by kind, closing a task left undone as missed, moving or skipping a single
date, entries counting a number of times (« 12 sessions »), « N times a week » habits, reminders
and notifications, a day or month view, and what the gamification will make of a slot kept or
missed.
