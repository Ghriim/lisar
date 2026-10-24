# Website · Routines

A routine is **the blueprint of a workout**: the blocks, movements and sets one means to do, each
set prescribed as reps and **a share of the movement's estimated 1RM** rather than as a load. A
workout is started from it in one gesture, with every load worked out from the person's records of
the day. As the records rise, the same routine asks for more: that is how it evolves.

The routines are listed in the « Routines » tab of the history card, on `/workouts`.
`/routines/:id` opens one routine.

## The list

The history card on `/workouts` carries two tabs: **« Historique »**, open by default, and
**« Routines »**.

- **« Routines »** lists the person's own routines, **by name**, accents and case aside. It is
  **not paginated**: a person keeps a handful of routines.
- Each row gives the name and how many movements it holds, and its actions: **« Démarrer »**
  (the play icon), the eye to open it, **« Dupliquer »**, and the bin, which deletes it after
  asking.
- **« Démarrer »** is offered only while no workout is in progress, as « Refaire » is.
- **« Nouvelle routine »**, at the top of the tab, opens a window asking its name; « Créer »
  creates it, empty, and goes to its page.

## A routine, on screen

On `/routines/:id`, laid out like [a workout](workouts.md#the-workout-on-screen):

- **Its panel** carries its name. Its actions: the pencil renames it, **« Démarrer »** starts a
  workout from it, **« Dupliquer »** copies it, the bin deletes it after asking.
- **« Exercices »** lists the blocks in order, « Bloc 2 » or « Bloc 2 · superset », with the same
  gestures as on a workout: arrows to reorder, a plus to add a movement (up to 6), a bin to remove,
  asking first when what goes holds sets. Adding opens the same picker, with one « Repos » field
  per movement picked.
- **Each movement** shows its note, its rest as a chip, and a « Retiré » chip if it was retired
  since. Its plus adds a set, its pencil edits its rest and its note.
- **Each set** is one line: its rank, then what it prescribes — « 5 reps · 80 % » — and **the load
  it would give today**, dimmed: « ≈ 72 kg », worked out as a workout started now would. A
  movement without an estimated 1RM yet reads « 1RM inconnu » instead. A movement without a load
  reads « 10 reps ». A set is corrected with its pencil and removed without a question.

No set is ticked here: a routine is never done, only started.

## A routine

| Field | Note |
| --- | --- |
| name | **required**, 100 characters at most — the workout it starts adds the day to it, within the workout's 128 |
| owner | the person who made it. **May be empty**: a routine shared with everyone, made from the back-office. None exists yet, and the website shows none |

A routine holds **blocks, movements and sets**, as a workout does: the same
[blocks and supersets](workouts.md#blocks-and-supersets), the same note and
[rest](workouts.md#the-rest-between-sets) on each movement, the same rule that only an offered
movement can be added (`movement_unavailable`), the same rule that a block never stays empty.

**Only a movement that tracks reps** can be added, with or without a load. A movement that tracks
a duration or a distance — a plank, a run, a farmer walk — is refused for now
(`movement_not_supported_in_routine`).

## A prescribed set

Every set is written out on its own: three identical sets, a pyramid, or anything else, is a
matter of what each set says.

| Measure | Bounds |
| --- | --- |
| reps | required, 1 to 10000 |
| share of the estimated 1RM | 1 to 100 %, by halves (`percentage_invalid`). **Required when the movement tracks a load** (`percentage_required`), **refused when it does not** (`percentage_not_tracked`) |

A prescribed set is always of the default [set type](../admin/set-types.md) for now. Other targets —
a duration, a distance, an RPE, a set type — will come; a prescribed set is shaped to take them.

## Starting a workout from a routine

« Démarrer » starts a new workout, now, laid out like the routine, and goes straight to its page.

- **What it takes over**: the blocks and their movements in the same order, each movement's note
  and rest, and **every set as something to do**, not ticked, of the default type: its reps, and
  **its load worked out** — see below. The workout is named after the routine and the day:
  « Push · 24/10/2026 ».
- **The load** is the set's share of **the movement's estimated 1RM** — its
  [current record](records.md), the best ever — **rounded down**, to the kilogram, or to the half
  kilogram for a unilateral movement. A movement without an estimated 1RM yet — never done, or
  never done with a load — gives **sets without a load**: the person types it in before ticking
  each set ([a set](workouts.md#a-set)). Once that workout has set a record, the next start
  works the load out.
- **Only what is offered now is taken over**, as when [a past workout is done
  again](workouts.md#starting-from-a-past-workout): a movement retired since is left out, a block
  left without a movement goes with it, and the workout's page says which movements were left out,
  once, on arrival.
- **A routine that gives no set cannot be started** (`routine_empty`): one without a set, or whose
  every movement was retired since.
- **One workout in progress at a time** holds here too (`workout_already_in_progress`).
- **The workout remembers its routine**: its page carries a link back to it. The routine is a
  blueprint, not a frame — the workout is then logged and corrected freely, like any other.

## Duplicating a routine

« Dupliquer » makes a new routine of the person's own, **« Copie de Push »**, holding the same
blocks, movements, notes, rests and sets, and goes to its page. A name that would run past 100
characters is cut there.

## Changing or deleting a routine

- **Changing a routine changes only the workouts started from it afterwards.** A workout already
  started holds its own copy of everything.
- **Deleting a routine keeps its workouts.** They lose their link to it, nothing else.

## Not here yet

Routines shared from the back-office and their screen, assembling routines into a program, a
prescription that progresses on its own (a load added when every rep was done), and targets other
than reps and a share of the 1RM.
