# Admin · Movements

`/workout/mouvements` — the common movements a workout is built from, and the families they sit
in. One screen for both: a family is only a name, so it is managed from a panel on this page
rather than a page of its own, like the muscle groups.

People will later be able to add movements of their own; this page shows **common movements
only**, the ones every account is offered.

## A movement

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, **unique among common movements**, ignoring case |
| description | optional, 5000 characters at most |
| video | optional, an absolute URL, 512 characters at most |
| family | exactly one; required, **nothing preselected** on a creation |
| primary muscle | exactly one; required, nothing preselected |
| secondary muscles | any number, **never the primary one** — picking a muscle as primary takes it out of the secondaries |
| equipments | any number; **none means bodyweight** |
| what a set records | reps, weight, duration, distance — see below |
| unilateral | done one side at a time |
| active | inactive: no longer offered to new workouts |

The name is unique among common movements only, and the check is the application's, not a database
index: a person's own movement will be allowed to share a common name.

**An equipment variant is a separate movement**, named in full: `Bench press (barbell)` and
`Bench press (dumbbell)` are two rows in the same family. Muscles are chosen from a list sorted by
group, and both lists can be searched.

### What a set records

Four switches: **reps, weight, duration, distance**. A set records at least one of reps, duration
or distance; weight only ever comes on top of one of them — a weight alone measures nothing.
Refused otherwise, with `measure_required`.

**On a creation, the equipments suggest the rest**: picking an equipment with a load
(`hasWeight`) switches weight on, one with a distance (`hasDistance`) switches distance on. It only
ever switches on, and the administrator adjusts after. **On an edit, changing the equipments
changes nothing else**: what the movement records was decided already.

### What a movement can take on

Only what is live: an **active family**, **active equipments**, and muscles that are **active and
in an active group**. What a movement already holds and was retired since **may stay** — the form
still shows it, and saving without touching it is accepted. Staying is not moving, as for muscles
in an inactive group.

The list says when a row holds something retired: a red "Famille désactivée" tag under the name,
and a red tag for a retired muscle or equipment.

- **Deactivating** a movement is one click, and one click back; it asks nothing.
- **Deleting** is final and asks first, and **is refused while a workout has logged the movement**
  (`movement_in_use`) — anyone's workout. Deactivating is the way to retire it: the history keeps
  it, new workouts no longer offer it.

## A family

| Field | Note |
| --- | --- |
| name | required, 128 characters at most, unique ignoring case |
| active | see below |

A family groups the variants of one movement worth following together — every bench press, every
row — so that progress can later be read across them.

- **Deactivating a family** withdraws every movement in it without touching the movements' own
  status; reactivating it gives them back as they were.
- **An inactive family takes no new movement**, neither created in it nor moved into it. A movement
  already in it stays and can still be edited.
- **A family is deleted only once it is empty** — no movement in it, active or not.

## The list

By name, with a search on the name, and filters that combine:

- active / inactive / all, opening on active — the movement's own status;
- **family**, **group**, **muscle**, **equipment** — each opening empty, inactive entries included:
  a filter narrows the list, it assigns nothing. **A muscle or a group matches through the primary
  muscle or any secondary one.** Choosing a group narrows the muscle filter to that group's muscles.

**Not paginated**, for the same reason as the equipment list.

## Seeded

41 families and 68 movements, all common and active; the table is readable in
`src/Fixtures/MovementFixtures.php`.
