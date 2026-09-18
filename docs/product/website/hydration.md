# Website · Hydration

A widget on the quest log, not a screen of its own: drinking is noted in passing, several times a
day, and sending someone to another page for it would mean they stop noting it.

## The widget

A glass of water and the day's progress — what has been drunk against the day's goal. Beside it,
one icon opens the window where everything else happens.

That is all it shows. The point of the widget is to be glanceable and to stay out of the way of
the quest log it sits next to.

## The window

Opened from the widget, it holds two things:

- **the day's entries**, each with the time it was logged and its volume, most recent first;
- **the ways to add one**: the shortcuts, tapped in a single gesture, and a free field in
  millilitres for whatever the shortcuts do not cover.

An entry can be **corrected** — its volume only, never its time — or **deleted**. Deleting one
asks nothing: it is re-added with a single tap, and a confirmation on every mis-tap would make
correcting one worse than making it.

## The rules

- **The current day, and nothing else.** No entry can be logged for yesterday, and no past day
  can be consulted or edited. What is missed is missed.
- **A day starts at midnight in `Europe/Paris`**, for everyone, whatever their own clock says.
  This is a placeholder: the profile stores no timezone yet, and the day the app has users
  elsewhere, this is the decision that will have to be reopened.
- **The goal is 1500 mL**, the same for everyone. A personal goal is planned and deliberately
  absent for now.
- **The day's goal is frozen into the day itself**, the first time something is logged. Raising
  the goal from 1500 to 2000 must not retroactively turn a day that was reached into a day that
  was missed. Nothing reads this yet — the statistics that will are worth not having to
  reconstruct.
- **A shortcut's volume is copied into the entry**, never referenced. Correcting a shortcut from
  250 to 200 mL changes what the next tap logs, and nothing about what was drunk yesterday.
- **Volume only.** No drink type, no hydration coefficient: what counts as hydrating is the
  person's business, and they log the volume they mean.
- **Millilitres only.** Other units come with the profile that will carry them.

## Not in v1

- any view of the past: history, averages, streaks, charts. A statistics domain will come, and it
  will read what is being recorded now.
- a personal goal, and the dynamic one (weight-based) that would follow the weight tracker
- reminders and notifications

## Midnight, with the page still open

The back end is never confused: **no request ever names a day**. An entry is logged to the day in
progress as the server counts it, so a page showing yesterday's totals still writes to today.
That is not a check, it is the shape of the API — there is nothing to get wrong.

The page is the one that goes stale, and the whole page, not just the widget: the totals, the
goal, the entries all belong to a day that is over. So **the front end reloads** as soon as it
notices the day has turned.

It notices by asking, never by computing: the day comes from the API, because the timezone days
are counted in is a back-end decision and duplicating it in the browser is how the two start
disagreeing. The watch belongs to the **page**, not to this widget — it is the whole screen that
goes stale, and there is now more than one tracker on it.

The check runs when the tab regains focus — the common case, someone opening their laptop the
next morning — and every five minutes on a screen left on.

## Open question

Whether deleting every entry of a day should erase the day, goal included, or leave it.
