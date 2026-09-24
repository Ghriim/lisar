# Website · Habits

A panel on the quest log: the habits the person has taken on, each with the last week at a glance
and — when it is theirs to tick — a button to mark today done.

Habits are not quests. A quest is done once and gone; a habit is never done, it is kept, one day
at a time, and what matters is the run of days, not a finish line. It is closer to the trackers
beside it than to the list below: a per-day record against a recurring intent.

## Subscribing

The habits themselves are a **catalogue an administrator maintains** (see the back-office page).
The person does not create habits yet — they **subscribe** to the ones they want to keep, from a
window opened off the panel, and unsubscribe from the ones they drop.

Unsubscribing **keeps the history**: it stops the habit appearing in the list and stops its run
counting, but the days already kept are not erased — the day habits earn experience, a dropped-then-resumed
habit must not have had its past wiped.

## The panel

One line per subscribed habit:

- **its name**;
- **the last seven days**, today included: a circle a day, empty for a day not kept, checked for a
  day kept. Seven small marks, read left to right, oldest to today — the run is seen, not counted
  at the person;
- **a button to keep it today**, and only when the habit is **manual**. Ticking it marks today
  done; it is the one gesture. A habit already kept today shows its last circle checked and needs
  no second tick.

A habit **fed by a tracker** carries no button: it is kept on its own the moment the tracker it
watches crosses its mark (10000 steps, 1500 mL). Its line shows the same seven days, filled in by
the tracker rather than by a tap.

## The rules

- **The current day, and nothing else.** A habit is kept for the day in progress; a day missed is
  missed, and no past circle can be filled in after the fact. The back end never names a day.
- **A day starts at midnight in `Europe/Paris`**, for everyone — the placeholder every tracker
  shares.
- **Keeping is binary.** Today is kept or it is not; there is no half. A habit whose progress is a
  quantity — a count toward a target — is fed by a tracker, not typed here. (Typing a count by
  hand is a later addition; the model allows it, the screen does not offer it yet.)
- **Only what you subscribed to shows.** The list is the person's own habits, not the catalogue;
  the catalogue is reached through the subscribe window.

## Not in v1

- creating one's own habits (the catalogue is admin-only for now);
- schedules other than daily (every day only; weekday and "N times a week" habits come later, with
  the run maths they need);
- typing a manual count toward a target;
- the experience a kept habit will earn, and the streak bonus that rewards a long run — a separate
  gamification domain will read what is being recorded now: each kept day, the moment it was kept,
  and how many days it has run.

## Midnight, with the page still open

As with the trackers, **no request names a day**: keeping a habit lands on the day the server
counts as in progress. The whole page goes stale when the day turns and reloads itself — the habit
panel inherits that watch, it does not add its own.
