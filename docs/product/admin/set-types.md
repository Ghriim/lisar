# Admin · Set types

`/workout/types-de-serie` — the kinds of set a workout can mark: a warm-up, a dropset, a set taken
to failure. Reference data an administrator maintains; people do not create set types.

## What a set type is

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, **unique ignoring case** — `Dropset` and `dropset` are one name |
| colour | required, **one of a fixed palette**, nothing preselected on a creation |
| active | inactive: no longer offered to new sets |
| default | **exactly one type carries it**: the one a set takes when it is logged without one |

**A set always carries a type.** A set logged or corrected without one takes the default — the
ordinary working set, `Travail` as seeded.

### The palette

`red`, `orange`, `yellow`, `green`, `teal`, `blue`, `purple`, `pink`, `grey`. The API stores and
answers the code, never a shade: each front end paints its own, so every type sits well on its
theme. A colour outside the palette is refused with `colour_unknown`. Two types may share a
colour.

No icon, no "counts in the volume" flag in this first version.

## Managing it

A list with a search on the name and the active / inactive / all filter, opening on active. A
window creates or edits a row.

- **Renaming or recolouring** a type changes it everywhere it is shown, past sets included: a set
  points at the type, not at a copy of its name.
- **Deactivating** takes a type out of what new sets are offered; the sets already carrying it keep
  it. One click, one click back: it asks nothing.
- **Deleting** is final and asks first, and **is refused while a logged set carries the type**
  (`set_type_in_use`) — anyone's set. Deactivating is the way to retire it.
- **The default** is a switch in the window, « Type par défaut », off on a creation. Switching it
  on gives the default to this type and takes it from the one that had it; it is **never switched
  off** (`default_set_type_required`) — the switch is locked on the default's own window. A retired
  type cannot take it (`default_set_type_inactive`). The default is marked « défaut » in the list,
  and is **neither deactivated nor deleted** (`set_type_is_the_default`): neither action is offered
  on its row.

The list is **not paginated**: a handful of rows.

## Seeded

Five types, all active: `Travail` (blue, **the default**), `Échauffement` (orange), `Dropset`
(purple), `Échec` (red), `Back-off` (blue). `Travail` is also created by the migration that
brought the default in, and every set logged without a type until then was given it.
