# Website · Planning

`/planning` is where a person lays out their weeks: **which routine on which day, which task to do
when, which days a habit counts**. Each of the three keeps its own page — the routines on
`/workouts`, the quest log on `/todo`, the habits on the dashboard — and this page is the one place
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
| **task** | a model of quest: title, description, category | [A task entry](#a-task-entry) |
| **habit** | a habit the person is subscribed to | [A habit entry](#a-habit-entry) |

Every kind carries the same schedule:

| Field | Note |
| --- | --- |
| rule | see [The rules of dates](#the-rules-of-dates) — **required** |
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

No single date can be moved or skipped on its own: to change a date, the rule is changed.

**Changing an entry changes the dates from today on.** What already fell — a slot kept, a slot
missed, a quest already created — stays as it was. **Deleting an entry** removes the dates to come
and keeps the past the same way: a workout started, a quest created, a habit day kept.

**Weeks start on Monday, and every day and time is read in `Europe/Paris`**, as everywhere in the
app.

## The page

**The week**, Monday to Sunday, opens on the current one; arrows go to the previous and next.
Seven columns, each with its day and date:

- what holds **within the day** sits at the head of its column;
- what has **a start time** sits on the hour grid, as tall as its duration — an entry without a
  duration is a mark at its hour.

Each date carries its kind's icon, its name and its state:

| Kind | States |
| --- | --- |
| training | **to come** · **kept** (a workout of that routine started that day) · **missed** (the day went by without one) |
| task | **to come** · **open** (its quest exists, not done — shown overdue once its day is past) · **done** |
| habit | **to come** · **kept** · **missed** — plus **kept, unplanned** for a habit kept on a day no entry asked for |

**The plus** at the top of the page creates an entry: it asks the kind — training, task, habit —
then what it ties, then the rule and the time. A click on a date opens its entry, to change it or
delete it after asking.

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

A task entry makes **quests from a model** it holds: a title, a description, a category. Subtasks
are not part of a model.

- **Recurring**: each date gets **its own quest, created that day** (from midnight), with that date
  as its due date. Before the day, it shows only on the planning, as to come.
- **A quest not done stays open**, overdue, as any quest past its due date: the next date creates
  its own quest beside it, and nothing is closed on the person's behalf. Three Mondays missed are
  three open quests — the quest log shows them overdue, each with its day.
- **Once**: the quest is **created at once**, with the date as its due date, so it can be seen
  coming in the quest log. Not done, it stays open and overdue.
- A planned quest carries the time of its entry, if any, and is otherwise an ordinary quest: done
  by hand, edited, deleted. Changing the entry's model changes the quests to come, not those
  already created.

## A habit entry

- **The days a habit counts are the person's**: a habit entry belongs to their subscription, not
  to the catalogue. **A subscribed habit without an entry counts every day**, as today; its entries,
  once it has some, say which days instead.
- **A run counts planned days only.** A day no entry asks for neither breaks the run nor extends it.
- **A habit can still be kept on an unplanned day**: the day is recorded as kept, shown as a bonus,
  and counts for nothing in the run.
- The seven circles of the [habits panel](habits.md#the-panel) show the planned days as they are,
  and the others dimmed. The panel shows the entries; they are created and changed here.
- Unsubscribing takes the habit's entries with it; subscribing again starts from every day.

## Navigation

« Planning » joins the main menu, after « Todo », with the calendar icon.

## Not here yet

Filtering the page by kind, closing a quest left undone as missed, moving or skipping a single
date, entries counting a number of times (« 12 sessions »), « N times a week » habits, reminders
and notifications, a day or month view, and what the gamification will make of a slot kept or
missed.
