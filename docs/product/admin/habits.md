# Admin · Habits

The catalogue of habits people can subscribe to. A habit is reference data here — a definition an
administrator maintains — exactly as the hydration shortcuts are, and it gets a back-office page
for the same reason.

## What a habit is

A row in the catalogue carries:

- **a name and an icon** — the icon a code from a fixed vocabulary, drawn its own way by each
  front end (as the hydration shortcut icons are);
- **how it is kept**: either **manual** (the person ticks it) or **fed by a tracker** — Steps,
  Hydration — which keeps it on their behalf;
- **for a tracker-fed habit, the mark to cross**: the value the tracker must reach that day for the
  habit to count as kept — 10000 steps, 1500 mL. Below the mark the day is not kept; at or above
  it, it is.

Every habit is **daily** for now: kept, or not, each day. Schedules other than daily are a later
addition.

## Managing it

The page lists the catalogue and lets an administrator create, rename and reconfigure, and remove
a habit — the same list-plus-form shape as the hydration shortcuts.

- **Creating** one names it, picks its icon, and chooses its source: manual, or a tracker with a
  mark to cross.
- **Editing** one changes any of those. Changing a tracker's mark from 10000 to 8000 changes what
  the next days need; it does not rewrite the days already kept, whose mark was frozen into them
  when they were recorded — a habit reached yesterday stays reached.
- **Removing** one is allowed even when people are subscribed to it. What was already kept is not
  erased — the history those days represent, and the experience they will have earned, outlives the
  catalogue entry.

## Not configured here

- the daily goal or timezone — those are application parameters, not catalogue rows;
- who is subscribed to what — a subscription is the person's, made on the website; the back-office
  owns the definitions, not the choices.

## Open question

Whether removing a habit from the catalogue should also stop it counting for the people still
subscribed, or leave their existing runs standing until they unsubscribe themselves.
