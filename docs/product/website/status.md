# Website · Status

`/statut` — the « Statut » window, as the System shows a hunter theirs: **a global level, and six
stats in two groups**, each with its level and how far it is from the next. It is the first
brick of the gamification — the *RPG* of *Life Is A RPG* — and starts simple: what earns
experience is not defined yet, so everyone stands at level 1 with 0 XP until the gains are wired.

## The stats

Six stats, in two groups. A group is only a heading: it has no level of its own.

| Group | Stat | Fed by |
| --- | --- | --- |
| **Physique** | Force · Endurance · Agilité | the movements of a workout — see [below](#a-movements-stats) |
| **Mental** | Intelligence · Esprit · Volonté | the tasks and the habits — how, to be detailed |

The trackers (hydration, weight, sleep, steps) feed no stat for now. A habit kept by a tracker
counts as a habit.

**The global level is worked out on the whole experience**, the six stats added up, whatever the
domain it was earned in.

### A movement's stats

Each movement of the catalogue names **one primary physical stat, required, and up to two
secondary ones**, never the primary again ([back-office](../admin/movements.md#a-movement)).
The physical experience a movement earns is shared out:

- **the primary alone**: it takes all of it;
- **with secondaries**: the primary takes 70 %, the other 30 % is split evenly between the
  secondaries — 30 % for one, 15 % each for two.

Experience is a whole number: a secondary's share is rounded down, and what rounding leaves goes
to the primary, so that nothing is lost nor made up.

How much a workout earns, and from what — sets, volume, duration — is for the gains to come.

## Levels

**Everyone starts at level 1, with 0 XP.** Going from level N to level N + 1 takes **50 × N²**
experience: 50 for level 2, 200 more for level 3, 450 more for level 4 — 14 250 in all to reach
level 10. The pace slows as one climbs.

**The same curve holds for every stat and for the global level.** The global level adds up six
stats' experience, so it climbs higher than any one of them: that is expected.

**Experience follows what earned it.** An action taken back — a task reopened, a habit unticked —
takes its experience back with it, levels included: a level can come down that way, and only that
way. There is no penalty: a slot missed or a task overdue costs nothing.

## The window

- At the top, **the global level**, the experience gathered, and a bar towards the next level
  with what is left to earn (« 120 / 200 XP »).
- Below, **Physique** then **Mental**, each with its three stats: the stat's name, its level, and
  its bar towards the next.

Nothing on it is clickable for now: it is read, not acted on.

## Level up

**Going up a level is announced by the System**, at the moment the experience is earned, on
whatever screen the person is: a message in the System's voice — « [Niveau supérieur] Force 5 → 6 »,
or « [Niveau supérieur] Niveau 12 » for the global level. It closes by itself, or by hand. A
level lost when an action is taken back is not announced.

**It comes with the gains**: nothing earns experience yet, so nothing is announced. The answer of
the write that earned it will say which levels went up, enriched through a Symfony event.

## Its entry

The menu no longer carries the level. The [dashboard](dashboard.md)'s status card shows it, and
opens `/statut`: that card is the window's only entry.

## Not here yet

What earns experience and how much, the mental stats' sources, penalties, a stat for the
trackers, levels per group, titles, ranks or rewards attached to a level, and any history of
what was earned.
