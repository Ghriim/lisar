# Website · Workouts

`/workouts` — where a workout is started, logged as it happens, finished, and found again
afterwards. `/workouts/:id` opens one workout; `/workouts/:id/complete` is where it lands once
finished.

## The page

- **When no workout is in progress**, a « Nouvelle séance » panel: an optional name and
  **« Démarrer »**, which starts it and **goes straight to its page**, `/workouts/:id`.
- **When one is**, its **summary** takes that place instead — there is never more than one:
  start, end, duration, feeling, note, and **one action, « Reprendre »** (the play icon), which
  opens its page. Editing, finishing and abandoning it, and its exercises, are on that page only.
- **The history**, under it: past workouts, latest first, 25 to a page with arrows to turn them.
  Each row gives the name, the day and how long it lasted, the
  movements done and how many sets. The eye opens it on its own page, to read it or correct it;
  « Revenir » goes back to the list. **« Refaire »** (the repeat icon) starts a new workout
  [from that one](#starting-from-a-past-workout) — offered only while no workout is in progress.

A workout in progress is not part of the history yet. It joins it when it is finished.

## The workout, on screen

On `/workouts/:id`, the one in progress and a past one are **the same sheet**: everything stays
editable once finished.

- **Its panel** carries its name, and lists every field: start, end (« En cours » while it runs),
  duration — **ticking every half minute** while it runs — feeling and note. Its actions:
  the pencil opens name, feeling and note in a window; **« Refaire »** starts a new workout
  [from it](#starting-from-a-past-workout) (finished, and only while none is in progress); the bin
  **abandons** it (in progress) or **deletes** it (finished), after asking.
- **« Bilan »**, on a finished workout only, between that panel and the exercises: see below.
- **« Exercices »** lists the blocks in order, each headed « Bloc 2 » or « Bloc 2 · superset »,
  with arrows to move it up or down, a plus to add a movement to it (up to 6) and a bin to remove
  it. Removing a block or a movement **that has sets** asks first; an empty one goes at once.
- **Adding** — the plus of « Exercices » for a new block, or a block's own plus — opens a picker:
  a search (accents and case aside) over the movements on offer, grouped by family, each a chip.
  For a new block, picking several makes a superset **in the order they were picked**, said back
  above the chips. Under the chips, **one « Repos » field per movement picked**, optional and
  empty: the [rest](#the-rest-between-sets) after each of its sets, in seconds (« 90 ») or as a
  stopwatch reads (« 1:30 »).
- **Each movement** shows its note, its rest as a chip — « Repos 1:30 », or « Repos 0 s » when
  it has none, so every movement reads the same way — and a « Retiré » chip if it was retired
  since. What it gave the last time is shown beside each set, not here. Its plus logs a set, its
  pencil edits its rest and its note; inside a superset, a bin removes it alone.
- **Each set** is one line: its rank in a small frame, **in its type's colour** — the type's name
  on hover — then its measures, in full white: « 10 reps · 60 kg », « 10 reps / côté » for a
  unilateral movement, durations as « 45 s » or « 1:30 », distances as « 800 m » or « 5,2 km ».
  An RPE follows on the same line, dimmed and unframed: « RPE 8,5 ».
  Under it, **the set of the same rank [the last time](#the-last-time)** — set 1 beside set 1,
  set 2 beside set 2, as plain text: « Dernière fois : 8 reps · 60 kg ». A set ranked past what was done then has none, and a set done then but not
  logged yet is not shown.
  A set that beat a [personal best](records.md) carries a gold chip per record under it, named
  alone — « 5RM » — from the moment it is ticked. Once the workout is finished, the chip opens
  the records page on that record.
  A set is corrected with its pencil and **removed without a question**: it is logged again in
  one gesture.
- **« Terminer »**, a button with the check icon, centred under the exercises — in progress only —
  **finishes** the workout and goes to the [closing page](#closing-a-workout). It stays disabled
  until the workout has a set and every set is ticked — what the API requires to finish it.
- **While the workout runs**, each set also carries a check, **« Valider »**, to tick it once it
  is done; a done set gets **a green left edge**, its measures still in full view, and its check
  becomes **« Décocher »**, for a mis-tap. A movement whose sets are all done gets one
  too. A finished workout shows neither the checks nor the green: every set in it was done.

### The rest between sets

A movement in a workout may carry **a rest**, from 1 second to an hour (`rest_invalid`): typed
when it is added, corrected or cleared with its pencil. None means no timer for it.

- **Ticking a set starts its movement's rest** — on the tap, not once the server answers: that is
  when the set was over. A superset changes nothing: each movement uses its own rest, so a movement
  without one starts none.
- **A bar held at the bottom of the screen** counts it down, as a stopwatch: « 1:30 », « 0:45 ».
  **« −15 s »** and **« +15 s »** move its end; −15 s goes no lower than zero. **« Passer »** ends
  it.
- **At zero** it beeps three times and buzzes, where the browser allows it — a phone often keeps
  both from a tab in the background, and iOS never buzzes — then **the bar disappears**. It never
  counts past zero.
- **Ticking another set** starts that one's rest instead; one whose movement has no rest ends the
  rest under way, since the next set is done. **Unticking the set that started it** stops it, and
  so does a tick the server refused.
- **Nothing of it is stored.** The rest really taken is not recorded, and the timer lives in the
  page: leaving the workout's page, or reloading it, loses it. A rest that ended while the tab
  was asleep closes without a sound — a beep minutes late would mean nothing.

### Closing a workout

`/workouts/:id/complete`, reached by finishing. A workout still in progress sent here goes back
to its page.

- **« Séance terminée »**: the same form as the pencil — name, feeling, note. « Enregistrer »
  saves and goes to the workout's page; « Annuler » goes there without saving.
- **« Bilan »** under it, the same card as on a finished workout's page:
  - **sets**, always; **volume** (reps × load, a unilateral set counting both sides) when a set
    carries both; **time under effort** when a set has a duration; **distance** when one has a
    distance. A total no set measures is not shown — it is not zero, it does not exist;
  - **the share of each muscle**, the most worked first, as a bar and a percentage: a set counts
    **1 for the movement's primary muscle and 0.5 for each secondary one**, and the percentages
    are out of all those counts together, so they add up to 100;
  - **« Records battus »**, when it beat any: each [personal best](records.md) it beat, by one of
    its sets or as a whole, with its value.

### Logging a set

A window with **only the measures the movement tracks**, then RPE and type, both « facultatif »
and empty. The empty type reads as the default type's name — it is what the server applies — and
the default is not offered again among the choices. The measures **open on the previous set** — the last one of this movement in this
workout, or else the first one of the last time: a load is a measurement, and the next is
almost always the last again. A weight takes a comma; a duration is typed in seconds (« 90 ») or
as a stopwatch reads (« 1:30 »).

## A workout

| Field | Note |
| --- | --- |
| name | optional, 128 characters at most. Without one, the front words a default (« Séance du 2 oct. ») |
| note | optional, 5000 characters at most |
| feeling | optional, 1 to 5, the overall feeling |
| started | set when it is started, **never rewritten** |
| finished | set when it is finished, **never rewritten** |

It is **logged live**: started now — empty, or [from a past workout](#starting-from-a-past-workout)
— filled set by set, then finished. Nothing is entered after the fact as a form. Everything stays editable once it is finished (sets,
movements, notes, feeling); only the two moments are fixed.

- **One workout in progress at a time.** Starting a second is refused
  (`workout_already_in_progress`); the first is finished or abandoned first.
- **A workout left in progress stays in progress** until it is finished or abandoned. Nothing
  closes it on its own.
- **Finishing** needs at least one set (`workout_empty`), and **every set ticked as done**
  (`workout_has_incomplete_sets`): one not done is done or removed first. Finishing one already
  finished changes nothing.
- **Abandoning** the one in progress deletes it, with everything logged in it. A finished
  workout can be deleted the same way.

## Blocks and supersets

A workout is an **ordered list of blocks**. A block holds one movement, or several done back to
back: a **superset** (at most 6). Blocks can be reordered. The same movement may come twice in a
workout, as two separate entries.

- A block is added at the end. A superset's movements keep the order they were given in.
- A movement can be added to an existing block, which turns it into a superset.
- **A block never stays empty**: removing its last movement removes it too. A whole block can
  also be removed at once.
- Each movement in a workout carries its own **note** (5000 characters at most) and its own
  [rest](#the-rest-between-sets).

**Only what is offered can be added**: an active movement in an active family
(`movement_unavailable` otherwise). A movement already in a workout and retired since stays
there, and the workout still shows it.

## A set

A set carries **exactly the measures its movement tracks**
([what a set records](../admin/movements.md#what-a-set-records)):

| Measure | Bounds |
| --- | --- |
| reps | 1 to 10000 |
| weight, in kg | 0 to 1000. Zero is a load: an empty machine, a bar on its own |
| duration, in seconds | 1 to 86400 |
| distance, in metres | 1 to 1000000 |

Every measure the movement tracks is required (`reps_required`, `weight_required`…). A measure it
does not track is refused (`weight_not_tracked`…): a weight on a push-up would be a number that
nothing reads.

On top of the measures, and both optional:

- **RPE**, 1 to 10 by halves (`rpe_invalid`);
- **a set type** from the [admin list](../admin/set-types.md), active only (`set_type_unknown`,
  `set_type_inactive`). **A set always carries one**: none named, at logging or at a correction,
  means the [default type](../admin/set-types.md), an ordinary working set — nothing is
  preselected, the server applies it. A set already carrying a type retired since keeps it through
  a correction.

**A unilateral movement's set covers both sides**, and its reps count per side: the front reads
it « 10 reps / côté ». No side is recorded.

Sets come in the order they were logged. A set can be corrected or removed at any time, during
the workout or after. Correcting one replaces its measures, RPE and type whole — not whether it
was done.

**A set is logged before it is done, and ticked once it is** — its measures are what is about to
be lifted. During the workout it starts not done; ticking and unticking are idempotent, and only
possible while the workout runs (`workout_finished` after). A set added to a finished workout
was done already: it comes ticked. So **a finished workout holds only done sets**.

## Starting from a past workout

A finished workout of the history can be **done again**: « Refaire » starts a new workout, now,
laid out like that one, and goes straight to its page.

- **What it takes over**: the name, the blocks and their movements in the same order, each
  movement's note and rest, and **every set as something to do**: same measures, RPE and type, **not
  ticked**. The sets are what is about to be lifted, as when one is logged before it is done:
  the load is adjusted if need be, then each set is ticked.
- **What it leaves**: the workout's own note and feeling, and its two moments. They tell what
  happened that day, not what is about to.
- **Only what is offered now is taken over.** A movement retired since, or in a family retired
  since, is **left out**, and a block left without a movement goes with it. The new workout's
  page says which movements were left out, once, on arrival. A set type retired since gives way
  to the default type.
- **A set follows what its movement tracks now.** A measure the movement no longer tracks is
  dropped; a set missing a measure it now tracks is left out.
- **One workout in progress at a time** holds here too: doing one again is refused while one is
  in progress (`workout_already_in_progress`), and the action is not offered then. Only a finished
  workout can be done again — the one in progress is, by definition, the one in the way.
- What is left out is left out of the copy only: the workout it was copied from does not change.

## The last time

Beside each set, the workout shows **what the same set gave the last time**, taken from the sets
of the latest finished workout, started before this one, in which that movement was done. If it
came twice in that workout, the sets of both follow one another, and are matched to this
workout's sets in that order. A movement never done before shows nothing beside its sets. Opened
on a past workout, it shows the workout before that one.

## The habit it keeps

A habit fed by the **workout tracker** ([habits](habits.md)) is kept on a day when at least its
mark of workouts were **finished** that day. Deleting a finished workout recounts its day.

## Not here yet

Workout templates and programs, a timer during a set (a plank, an interval), people's own
movements, and statistics.
