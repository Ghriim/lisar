# Website · Task list

`/todo` — the person's tasks, and everything they do to them. The same log also sits on the
[dashboard](dashboard.md), beside the trackers; it is one component in both places, so the two
cannot drift apart. Filters will come to `/todo` only.

What a task earns is not defined yet: the lines and « Aujourd'hui » show its slot, `+— XP`
(`features/tasks/taskExperience.ts`).

## What the page shows

The section is titled `[ TÂCHES ]`, outside its card, with two buttons on the right: **Gérer les
catégories** (a folder) and **Créer une tâche** (a plus, the main one). One card holds every task.
Two tabs: **`Actives · {n}`** — the number of open root tasks — and **`Terminées`**.

**A task is shown in one place only.** In the active tab:

- **« Aujourd'hui »** comes first, always: the open tasks due today and the late ones, whatever
  their category, plus those of them ticked off today, still ticked. Its heading is lit — a star,
  the accent, an accent rule — and reads `mer. 25 nov. · retards inclus`, then on the right
  `{done} / {total} · +{xp} XP`. Its lines are lit too, and each wears its category's name; a late
  one is red.
- then **each category**, holding the rest: the tasks without a date, and the ones due later
  (« Échéance ven. 28/11 »). Its heading folds and unfolds it — open to start with — and gives
  its count; when some of its tasks sit in « Aujourd'hui », it says so on the right:
  « +1 dans Aujourd'hui ».

As soon as a task is due today or late it moves to « Aujourd'hui » and out of its category. The
done tab is the categories alone.

**A line carries two actions and no more**: the box on the left ticks the task off — and
unticks it — and the title opens it. Everything else — editing, adding a subtask, deleting — lives
inside the task's own window, because those are done once in a while and ticking off is done
constantly.

A line shows, after the title, how far its subtasks have got — `1 / 2 sous-tâches` — and under it
its chips: its category (in « Aujourd'hui » only), « Priorité haute » in amber (high only — normal
and low say nothing), « En retard · 30/09 » in red, or « Échéance … ». On the right, what it
earns: `+30 XP`, shown `+— XP` until the gains exist. Subtasks are not unfolded in the line: they
are acted on in the task's window.

**Default sort, in full**: by category first — a task without one sorts last — then by priority,
high to low, again with the ones lacking a priority last, then the newest first. Ties had to break
somewhere.

**The list is not paginated.** A personal todo list is small, and a page of a list sorted by
category then priority tells you nothing useful.

## A task

| Field | Required | Note |
| --- | --- | --- |
| title | yes | the only required field |
| description | no | |
| due date | no | a calendar day, `YYYY-MM-DD`, **no time of day** |
| priority | no | one of three, highest first: Haute, Normale, Basse; Normale when left empty |
| category | no | exactly one, common or personal |
| parent task | no | present on a subtask only |

**The priorities are a fixed list of three**, not reference data: *Haute*, *Normale*, *Basse*,
in that order. The API carries a code (`high`, `normal`, `low`) and the front end words it and
colours it — red, blue, grey. There is no back-office page for them: changing the list means
changing the code. A task created without one gets **Normale**, applied by the server.

**A subtask is a full task**: same fields, its own due date, priority and category. The
only thing that sets it apart is being attached to a parent — and it cannot have subtasks of its
own. One level, and one only.

**An overdue due date is not a state**, only a way of displaying one.

**A task can come from the planning**: a [task entry](planning.md#a-task-entry) makes one per
date, from its model — title, description, category — with that date as its due date and the
default priority. It is then an ordinary task: ticked off, edited, deleted by hand. It carries
no time of day; the entry's time only places the date on the planning.

## The four states

| State | How it is reached |
| --- | --- |
| to do | where everything starts |
| in progress | **derived**: at least one subtask done, but not all |
| ready to close | **derived**: every subtask done |
| done | closed **by hand**, never automatically |

The state and the `(X/Y)` count say two different things and are both worth having: the state is
the word — *in progress* — and the count is the distance. A task at `(3/4)` and one at `(1/4)` are
both *in progress*.

The two middle states **only exist for a task that has subtasks**. A task without any goes
straight from *to do* to *done*; it cannot be "started". Neither is stored, and the system never
closes anything on the person's behalf.

## What the page enforces

- **A parent cannot be closed while one of its subtasks is still open.** Closing a parent never
  closes its subtasks either: the person ticks the last one off, then closes the parent.
- Subtasks are ticked off independently of one another.
- **A subtask cannot be moved** under another parent, nor promoted to a task of its own.
- **Deleting means deleting.** No archive, and a parent takes its subtasks with it.
- **Editing replaces the task whole.** What the form leaves out is cleared, and the default
  priority is not silently put back: clearing a priority is something a person means.

## Reading a task, then acting on it

The eye opens the task **read-only** in a window headed *Détails de la tâche* — generic,
because the task's own title is the first field inside it. Field by field under its own heading,
**on the same grid and in the same order as the form**: the title with its state beside it, the
detail across the width, then the short fields two to a line — category and priority, then
the due date. The state carries no heading: the chip already says the word. A field is where it
was when it was filled in, so reading and editing do not ask the eye to relearn the layout. A
field with no value says so rather than disappearing — "Aucune échéance" — and the reader can
tell an empty field from a forgotten one.

The actions come next, then the subtasks. The window holds four: **terminer** (or rouvrir),
**modifier**, **ajouter une sous-tâche**, **supprimer**. Editing does not open a second window:
the same one becomes the form. Deleting asks first, and says what it takes with it.

**Each subtask carries its own actions there too** — terminer, modifier, supprimer — so working
through a task's subtasks does not mean closing and reopening windows. The list behind updates
as they are ticked off, and so does the window: it re-reads the task rather than showing what it
was when it opened.

A subtask's window offers no "add a subtask": there is one level, and one only.

## Writing a task

The form is a **modal**, opened by the `+` in the panel header — the page shows the list first —
or reached from a task's own window.

Nothing is preselected: no category, no priority. The only default anywhere is the priority,
**Normale**, and it is applied **by the server** to a payload that named none.

## Categories

Categories are not the todo list's own: they are one set the whole app sorts things into — a
habit sits in one too ([habits](habits.md#the-panel)). The task list is where the person manages
theirs, because tasks are what their own categories are for.

A task has no icon of its own: its row wears **its category's**, when that is a common one — a
personal category has none.

Two origins, one single list to choose from:

- **common categories**, managed in the back-office. They cannot be renamed or hidden; they are
  used as they are.
- **personal categories**, created here and visible to this account only.

The person manages their own — create, rename, delete — from a modal that lists the common ones
alongside, greyed out. The list they choose from is the list they manage.

- A common category answers **not editable** rather than not found: it is on their screen, and
  pretending otherwise would puzzle them.
- A category **still holding tasks cannot be deleted**; they empty it first. A personal category
  holds nothing but tasks: habits only sit in common ones.
- A label only has to be unique **within what one person sees**: their own plus the common ones.
  Two accounts may each have a "Sport", and a personal "Sport" does not block a common one.

## Not in v1

- **recurrence** and **scheduling** ("do it on", as opposed to "due by"): standalone components,
  to be specified on their own and then plugged in here
- sharing or assigning a task to someone else — that comes with the friends list
- projects grouping tasks: the list is flat
- reminders and notifications

## Open questions

- which filters and sorts the page should offer beyond the default — to be settled through use
- is a manual order, drag and drop, expected?
- how far back the completed view should go
- which timezone "overdue" is computed against: the profile stores none today
