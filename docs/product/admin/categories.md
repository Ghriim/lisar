# Admin · Common categories

`/categories` — the categories every account picks from, under its own **Catégories** section of
the menu: a category is not the todo list's any more, every domain that sorts things draws from
the same set. Today that is **tasks** and **catalogue habits**.

## What the page shows

The common categories and nothing else. **A personal category does not exist on this surface**:
asking for one answers *not found*, like everything belonging to an account.

## A common category

- **a label**, unique among the common ones;
- **an icon**, required — a code from a fixed vocabulary (`CategoryIconRegistry`: maison, marche,
  lecture, sport…), each front end drawing its own glyph and word for it. **It is the icon of
  everything filed in the category**: a task and a habit have none of their own. A personal
  category has no icon, so what sits in one is drawn without.

## The rules

- **Everyone sees them; nobody but this page renames or deletes them.** On the website they are
  shown greyed out, alongside the ones the person created for themselves.
- **A common category cannot be deleted while anything sits in it** — any account's tasks, not
  just the administrator's own, and any catalogue habit, retired ones included. Emptying the
  tasks is the account holders' business, which is worth knowing before promising anyone a
  deletion; emptying the habits is done here, on the habit page.
- **A personal label does not block a common one.** Naming a common category "Sport" while
  someone already has a personal "Sport" is allowed: personal labels are private, and the website
  shows the two sets apart, which keeps the duplicate readable. Consulting private labels in
  order to refuse would leak that one is taken.

## Scope

**One set, for every domain.** A category says what a thing is about — home, work, health —
whatever kind of thing it is, so a task and a habit about the same part of a life sit under the
same label. A new domain that wants to sort its things plugs into this set; it does not grow its
own.

What each domain accepts differs:

| Domain | Takes | Who sets it |
| --- | --- | --- |
| task | a common category or one of the person's own | the person |
| catalogue habit | a common category only | the back-office, on the [habit page](habits.md) |
