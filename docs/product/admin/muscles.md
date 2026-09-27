# Admin · Muscles

`/workout/muscles` — the muscles a movement targets, and the groups they sit in. One screen for
both: a group is only a name, so it is managed from a panel on this page rather than a page of its
own.

A movement will target **one primary muscle and any number of secondary ones**; that is the
movement's business, and nothing here changes because of it.

## A muscle group

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, unique ignoring case |
| active | see below |

The groups serve to sort the muscles here and, later, to add statistics up by group. `Other`
holds what is not a muscle group as such — `Cardio`, today.

- **Deactivating a group** withdraws every muscle in it from new movements **without touching the
  muscles' own status**: reactivating the group gives them back exactly as they were.
- **An inactive group takes no new muscle** — neither created in it nor moved into it. A muscle
  already in it stays there and can still be renamed: staying is not moving.
- **A group is deleted only once it is empty** — no muscle in it, active or not. Every muscle sits
  in a group; to retire one that still holds some, deactivate it.

## A muscle

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, **unique across every group**, ignoring case |
| group | exactly one; required, **nothing preselected** on a creation |
| active | inactive: no longer offered to new movements |

Unique across every group rather than within its own: a movement lists its muscles by name, and two
"Upper chest" would read as one.

**A muscle is offered to new movements only when it is active and its group is too.** The list
says so on the row: a muscle in an inactive group carries a "Groupe désactivé" tag next to its
group. The active / inactive filter reads the muscle's own status only.

Deleting a muscle is final and asks first. As with equipment, **once movements exist, deleting one
a movement targets will be refused**.

## The list

By group, then by name, with a search that matches the muscle's name or its group's, and two
filters that combine:

- active / inactive / all, opening on active — the muscle's own status;
- **group** — any (the default), or one of them, inactive groups included: the filter narrows the
  list, it assigns nothing, so it offers every group there is. **Not paginated**, for the same reason as the equipment list.

## Seeded

Common names at the precision a workout is planned at — upper and lower chest, not the pectoralis
minor. Neck is left out for now.

| Group | Muscles |
| --- | --- |
| Chest | Upper chest · Mid chest · Lower chest |
| Back | Lats · Traps · Upper back · Mid back · Lower back |
| Shoulders | Front delts · Side delts · Rear delts |
| Arms | Biceps · Triceps · Forearms |
| Core | Abs · Obliques |
| Legs | Quadriceps · Hamstrings · Adductors · Abductors · Calves · Hip flexors |
| Glutes | Glutes |
| Other | Cardio |
