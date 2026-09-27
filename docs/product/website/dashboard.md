# Website · Dashboard

`/` — where the person lands once signed in: the day at a glance.

## What the page shows

- in a row at the top, the tracker widgets — [hydration](hydration.md), [steps](steps.md),
  [sleep](sleep.md), [weight](weight.md);
- below them, the [quest log](tasks.md) and, beside it, the [habits](habits.md) panel.

The quest log here is the same one as on `/todo`, with every window it opens.

## When the day turns

A page left open past midnight is stale as a whole — the day's totals, its goals, its entries.
The dashboard watches for the day to change and reloads. The day is the one the API answers
with, never the browser's, since which timezone days are counted in is a back-end decision.
