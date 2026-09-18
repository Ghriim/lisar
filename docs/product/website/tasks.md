# Website · Quest log

`/` — the screen the app is for: the person's tasks, and everything they do to them.

Gamification (XP, rewards, penalties) is **out of scope**. It will be plugged onto this page
later without calling any of it into question.

## What the page shows

Root tasks, grouped by category. Two tabs: the tasks in progress, and a separate view for the
ones already done.

**A row carries two actions and no more**: ticking the quest off, and opening it. Everything
else — editing, adding a sub-quest, deleting — lives inside the quest's own window, because those
are done once in a while and ticking off is done constantly. A list crowded with five icons per
line makes the one action that matters harder to hit.

**A task with subtasks is folded**, and folded by default: it reads as one line until someone
asks for more. The line carries how far it has got, right after the title — `(1/2)`, meaning one
subtask done out of two — so unfolding is for acting on a subtask, not for finding out where
things stand. A task without subtasks has neither the control nor the count.

**Default sort, in full**: by category first — a task without one sorts last — then by priority
weight, again with the ones lacking a priority last, then the newest first. Ties had to break
somewhere.

**The list is not paginated.** A personal todo list is small, and a page of a list sorted by
category then priority tells you nothing useful.

A *done* task leaves the main list and is found again in the completed view, where it can be
reopened.

## A task

| Field | Required | Note |
| --- | --- | --- |
| title | yes | the only required field |
| description | no | |
| due date | no | a calendar day, `YYYY-MM-DD`, **no time of day** |
| priority | no | from the back-office set; the default applies when left empty |
| category | no | exactly one, common or personal |
| tags | no | several, free text, private to the account |
| parent task | no | present on a subtask only |

**A subtask is a full task**: same fields, its own due date, priority, category and tags. The
only thing that sets it apart is being attached to a parent — and it cannot have subtasks of its
own. One level, and one only.

**An overdue due date is not a state**, only a way of displaying one.

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

The eye opens the quest **read-only** in a window headed *Détails de la quête* — generic,
because the quest's own title is the first field inside it. Field by field under its own heading,
**on the same grid and in the same order as the form**: the title with its state beside it, the
detail across the width, then the short fields two to a line — category and priority, then tags
and due date. The state carries no heading: the chip already says the word. A field is where it
was when it was filled in, so reading and editing do not ask the eye to relearn the layout. A
field with no value says so rather than disappearing — "Aucune échéance" — and the reader can
tell an empty field from a forgotten one.

The actions come next, then the sub-quests. The window holds four: **terminer** (or rouvrir),
**modifier**, **ajouter une sous-quête**, **supprimer**. Editing does not open a second window:
the same one becomes the form. Deleting asks first, and says what it takes with it.

**Each sub-quest carries its own actions there too** — terminer, modifier, supprimer — so working
through a quest's sub-quests does not mean closing and reopening windows. The list behind updates
as they are ticked off, and so does the window: it re-reads the quest rather than showing what it
was when it opened.

A sub-quest's window offers no "add a sub-quest": there is one level, and one only.

## Writing a task

The form is a **modal**, opened by the `+` in the panel header — the page shows the list first —
or reached from a quest's own window.

Nothing is preselected: no category, no tag, no priority. The only default anywhere is the priority,
and it is applied **by the server** to a payload that named none.

## Categories

Two origins, one single list to choose from:

- **common categories**, managed in the back-office. They cannot be renamed or hidden; they are
  used as they are.
- **personal categories**, created here and visible to this account only.

The person manages their own — create, rename, delete — from a modal that lists the common ones
alongside, greyed out. The list they choose from is the list they manage.

- A common category answers **not editable** rather than not found: it is on their screen, and
  pretending otherwise would puzzle them.
- A category **still holding tasks cannot be deleted**; they empty it first.
- A label only has to be unique **within what one person sees**: their own plus the common ones.
  Two accounts may each have a "Sport", and a personal "Sport" does not block a common one.

## Tags

Free text, several per task, **private to the account**, trimmed and de-duplicated. Two different
cases are two different tags.

**The tags already used are offered back** as chips to toggle while writing a task. Typing a new
one still works — the field is free text — but the offer is what stops the same word being
spelled three ways.

**A tag is a row on the account, not a word on the task.** It survives the tasks that carried it,
which is what lets it be offered again.

## Not in v1

- **recurrence** and **scheduling** ("do it on", as opposed to "due by"): standalone components,
  to be specified on their own and then plugged in here
- sharing or assigning a task to someone else — that comes with the friends list
- projects or quests grouping tasks: the list is flat
- reminders and notifications

## Open questions

- which filters and sorts the page should offer beyond the default — to be settled through use
- is a manual order, drag and drop, expected?
- how far back the completed view should go
- which timezone "overdue" is computed against: the profile stores none today
- should a tag nobody uses any more be offered for deletion, or quietly kept?
- renaming a tag across every task that carries it: worth doing?
