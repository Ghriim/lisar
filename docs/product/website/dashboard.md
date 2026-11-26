# Website · Dashboard

`/` — where the person lands once signed in: the day at a glance.

## What the page shows

- at the top, full width, the **status card**;
- in a row below it, the tracker widgets — [hydration](hydration.md), [steps](steps.md),
  [sleep](sleep.md), [weight](weight.md);
- below them, the [task list](todos.md), two thirds of the width, and beside it a column with
  the training of the day, then the [habits](habits.md) panel.

The task list here is the same one as on `/todos`, with every window it opens, but across every
active TODO and without tabs: « Aujourd'hui » first — due today or late — then the favourite tasks
by category ([TODOs](todos.md#what-the-page-shows)).

## The status card

A compact card, the player on the left and the six stats on the right:

- the global level in a **medallion** — a square turned on its corner. Clicking it opens the
  [Statut](status.md) window: it is the window's only entry;
- the person's name and, in amber, their **title** — « Titre : — » until titles exist;
- a bar of **25 segments** towards the next level, a segment lighting up once it is whole, then
  `340 / 500 XP`;
- the six stats, in this order: FOR, AGI, END, INT, ESP, VOL — each its three letters over its
  level, the full name on hover. No icons.

Not shown: the level beside the name (the medallion says it), a rank, a run, or anything like
« X XP to go ».

## The training of the day

Titled `[ ENTRAÎNEMENT ]`, with a calendar on the right — « Voir ma journée » — that opens the
[planning](planning.md). One line per session planned today, by time; the sessions without one
come last. Tasks never show here, and sessions never show among the tasks.

A line: the time (`07:30`, or `--:--` dimmed, « Sans horaire » on hover), a rule, the routine's
name with `{duration} · {n} exercices` under it, and on the right **Démarrer** (a play button),
which starts a workout from the routine and opens it — not offered while another workout is in
progress. A session done today is dimmed, and a round check takes the button's place. No sport
icon, no muscles, no list of exercises.

**A workout under way comes first, in green** — lit border, green time, a pulsing dot: the hour it
started, its name, « En cours · 33 min » (counted on, every half minute), and **Reprendre** (a play
button, green) which opens it. Started from a session planned today, it stands in that session's
place rather than beside it: the session is never listed twice. A workout under way also hides
« Jour de repos » and « Aucune séance prévue » — something is going on, whatever the plan said.

Nothing today:

- **a rest day** when there are training entries, just none today: « Jour de repos », and the
  next session within two weeks — « Prochaine séance · Vendredi · Bas du corps »;
- **nothing planned** when there is no training entry at all: « Aucune séance prévue », and
  « Planifier une séance », which opens the planning.

## When the day turns

A page left open past midnight is stale as a whole — the day's totals, its goals, its entries.
The dashboard watches for the day to change and reloads. The day is the one the API answers
with, never the browser's, since which timezone days are counted in is a back-end decision.
