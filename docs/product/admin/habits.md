# Admin · Habits

The catalogue of habits people can subscribe to. A habit is reference data here — a definition an
administrator maintains — exactly as the hydration shortcuts are, and it gets a back-office page
for the same reason.

## What a habit is

A row in the catalogue carries:

- **a name**. A habit has **no icon of its own**: it wears its category's, so a habit in no
  category is drawn without one;
- **how it is kept**: either **manual** (the person ticks it) or **fed by a tracker** — Steps,
  Hydration, Workouts — which keeps it on their behalf;
- **a category, or none** — always a [common one](categories.md): the catalogue is everyone's,
  so a personal label, the administrator's own included, has no place on it. The person does not
  change it; their own categories are for their own things, their tasks;
- **for a tracker-fed habit, the mark to cross**: the value the tracker must reach that day for the
  habit to count as kept — 10000 steps, 1500 mL, 1 workout. Below the mark the day is not kept; at
  or above it, it is. The workout tracker's figure is **how many workouts were finished that day**:
  one in progress does not count yet.

Every habit is **daily** for now: kept, or not, each day. Schedules other than daily are a later
addition.

## Managing it

The page lists the catalogue and lets an administrator create, rename and reconfigure, and remove
a habit — the same list-plus-form shape as the hydration shortcuts.

- **Creating** one names it, files it in a category if it belongs to one — which gives it its icon — and chooses its source: manual, or a tracker with a
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
