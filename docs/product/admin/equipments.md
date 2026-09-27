# Admin · Equipments

`/workout/equipements` — the equipment movements are done with: a barbell, a jump rope, a leg
press machine. Reference data an administrator maintains; people do not create equipment.

## What an equipment is

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, **unique ignoring case** — `Barbell` and `barbell` are one name |
| with a load (`hasWeight`) | a movement done with it asks for a weight when it is logged |
| with a distance (`hasDistance`) | a movement done with it asks for a distance — the rower, the bike, the treadmill |
| active | inactive: no longer offered to new movements |

No icon, no category, no translation in this first version.

**Bodyweight is not an equipment.** A movement with no equipment at all is done with the body's own
weight; a "bodyweight" row would only say the same thing twice.

**One row per machine**, never a generic "machine": which movement is done on it depends on which
machine it is, so a leg extension machine and a leg curl machine are two rows.

A resistance band is `hasWeight = false`: it has a resistance, but not one logged in kilograms.
The assisted pull-up machine is `hasWeight = true`: its weight is a counterweight, but it is still a
number to log.

## Managing it

A list with a search on the name and three filters that combine:

- active / inactive / all, opening on active;
- **load** — with, without, or either (the default);
- **distance** — with, without, or either (the default).

A window creates or edits a row.

- **Deactivating** takes an equipment out of what new movements are offered; the movements already
  done with it keep it. One click, one click back: it asks nothing.
- **Deleting** is final and asks first, and **is refused while a movement uses the equipment**
  (`equipment_in_use`) — deactivating is the way to retire it.

The list is **not paginated**: a few dozen rows, and the search narrows them faster than pages
would.

## Seeded

The fixtures load 43 rows: free weights and loaded machines (`hasWeight`), benches, bars and
accessories (neither flag), and the three cardio machines (`hasDistance`).
