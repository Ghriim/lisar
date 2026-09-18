# Admin · Priorities

`/priorites` — the priority set everyone picks from, and it belongs here alone.

## What a priority is

A **label**, a **weight** (lower sorts first, inside a category), a **colour** the front ends
render, and possibly the flag that makes it the default. None of it has any effect beyond display
and sorting: a priority does not change what happens to a task.

## There is always exactly one default

**The default is never dropped, only handed over.** Giving it to another priority takes it from
the one that had it, and the very first priority ever created gets it whether or not anyone asked
for it. The reason is simple: a task created without a priority has to get something.

Consequently the form refuses to *unset* the default — the switch is disabled on the priority
that currently holds it. Move it instead.

## Deleting

Two reasons a priority stays, and they are reported **together** so the whole answer arrives at
once:

- **it is the default** — hand that over first;
- **tasks carry it**, across every account, not just the administrator's own.

## Open question

Reordering by dragging rows, instead of editing weights by hand.
