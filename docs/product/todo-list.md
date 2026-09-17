# Todo list

The first functional domain of lisar. Day-to-day task management, multi-account, with
tasks strictly private to their owner.

Gamification (XP, rewards, penalties) is **out of scope**: it will be plugged onto this
domain later, without calling it into question.

## 1. Scope

**In v1**

- creating, reading, updating and deleting a task
- subtasks, one level deep
- categories: a reference set managed in the back-office + personal categories
- free-form tags, private to each account
- priorities: a reference set managed in the back-office
- due date (date only)
- states derived from subtask progress
- a sorted list and a completed-tasks view
- back-office: managing reference categories and priorities

**Out of scope for v1**

- **recurrence** and **scheduling** ("do it on"): standalone, reusable components (tasks,
  workouts, …), specified separately then plugged in here
- gamification
- sharing and assigning tasks between accounts — these come with the contacts/friends list
- projects or quests grouping tasks: the list is flat
- subtasks more than one level deep
- reminders and notifications

## 2. The task

| Field | Required | Note |
| --- | --- | --- |
| title | yes | the only required field |
| description | no | |
| due date | no | **date only**, no time of day |
| priority | no | from the back-office reference set; the default applies when left empty |
| category | no | exactly one; either a reference or a personal category |
| tags | no | several; free-form text, private to the account |
| owner | yes | a task is private |
| parent task | no | present on a subtask only |

**A subtask is a full task**: it carries the same fields, including its own due date,
priority, category and tags. The only thing that sets it apart is being attached to a
parent task. A subtask cannot itself have subtasks (one level only).

## 3. States

| State | How it is reached |
| --- | --- |
| to do | initial state |
| in progress | **derived**: at least one subtask completed, but not all of them |
| ready to close | **derived**: every subtask is completed |
| done | closed **manually**, never automatically |

Watch out for:

- *in progress* and *ready to close* **only exist for a task that has subtasks**. A task
  without subtasks goes straight from *to do* to *done*; it cannot be "started".
- both of those states are **derived** from subtask progress: the user does not pick them,
  and the system never closes a task on their behalf.
- an overdue due date **is not a state**: it is a display-level computation.

## 4. Business rules

- **Deleting means deleting from the database**, not archiving. Deleting a parent task
  deletes its subtasks in cascade.
- **A parent cannot be closed while a subtask is still open.** Closing a parent never
  closes its subtasks.
- A subtask **cannot** be moved under another parent, nor promoted to a root task.
- Subtasks are ticked off independently of one another.

## 5. Reference data

### Priorities

Managed **in the back-office only**. A priority carries a **label**, a **weight** (which
gives the sort order) and a **colour** (front-end display).

- one priority is flagged as the **default**; it applies to tasks created without an
  explicit priority
- a priority **used by at least one task cannot be deleted**
- no functional effect beyond display and sorting

### Categories

Two origins, one single use on the user side:

- **reference categories**, managed in the back-office. The user can neither rename nor
  hide them: they use them as they are.
- **personal categories**, created by the user and visible to them only.

Rules:

- a category **that is in use cannot be deleted**
- the reference set is **specific to the todo list**: the other domains (workout,
  trackers…) will have their own

### Tags

Free-form text, several per task, **private to each account**. No shared reference set.

## 6. Journeys

**Website (the user)**

- create, edit, tick off and delete their tasks and subtasks
- **default sort: by category, then by priority within a category**
- a *done* task **disappears from the main list**; a separate completed-tasks view brings
  it back
- additional filters and sorts: to be settled through use (see §7)

**Back-office**

- manage the reference categories
- manage the priorities, including which one is the default
- manage users (see `user-account.md`)
- **an administrator never sees a user's tasks**

## 7. Settled along the way

- **There is always exactly one default priority.** The default is never dropped, only handed
  over: setting it on another priority takes it from the one that had it, and the very first
  priority ever created gets it whether or not anyone asked. A task created without a priority
  has to get something.
- **A priority cannot be deleted while it is the default, or while any task carries it.** The
  two reasons are reported together, so the back-office learns both in one go.
- **A personal label does not block a common one.** An administrator naming a common category
  "Sport" while someone already has a personal "Sport" is allowed: personal labels are private,
  and the website shows the two sets apart, which keeps the duplicate readable.
- **A common category cannot be deleted while any account's tasks sit in it** — across every
  account, not just the administrator's own.
- **The person manages their own categories** — create, rename, delete — and sees the
  reference ones alongside, greyed out: the list they choose from is the list they manage.
  A reference category comes back as *not editable* rather than *not found*, because they can
  see it and pretending otherwise would only puzzle them. A category still holding tasks
  cannot be deleted; they empty it first.
- **Two accounts may each have a category of the same name.** The lists never meet, so there
  is nothing to collide. A label only has to be unique within what one person sees: their own
  categories plus the reference ones.
- **The tags already used are offered back** when writing a task, as chips to toggle. Typing
  a new one still works — the field is free text — but the offer is what stops the same word
  being spelled three ways.
- **A tag is a row on the account, not a word on the task.** The account keeps its tags even
  when no task carries them any more, which is what lets the front ends offer the ones
  already in use and makes filtering on a tag a join rather than a text search. Labels are
  trimmed and de-duplicated; two different cases are two different tags.
- **Editing a task replaces it whole.** Whatever the caller leaves out is cleared, and the
  default priority is not silently put back: clearing a priority is something a person means.
- **Default sort, in full**: category first (a task without one sorts last), then priority
  weight (again, without one sorts last), then the newest first. Ties had to break somewhere.
- **The list is not paginated.** A personal todo list is small, and a page of a list sorted by
  category then priority tells you nothing useful.
- **A due date is a calendar day**, `YYYY-MM-DD`, with no time of day anywhere in the API.
- **Someone else's task, subtask or personal category is answered as not found**, never as
  forbidden: its very existence is none of this account's business.
- **An administrator is a user too.** They have their own todo list like anyone else; the
  back-office role only adds what §6 describes.

## 8. Open questions

- the exact set of filters and sorts offered on the website — to be settled through use
- is a manual order (drag and drop) expected?
- the completed-tasks view: how far back does it go, and can a done task be reopened?
- which timezone is "overdue" computed against? The profile does not store one today
  (see `user-account.md`)
- should a tag the account no longer uses be offered for deletion, or quietly kept?
- renaming a tag across every task that carries it: worth an endpoint?
