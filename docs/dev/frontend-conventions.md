# Front-end conventions

What holds across both front ends, `frontend/website` and `frontend/admin`. Anything specific to
one of them lives in its own README.

## 1. Buttons

- **A button is labelled with a verb, and with the verb alone.** `Créer`, `Enregistrer`,
  `Annuler`, `Se déconnecter` — never `Nouvelle quête`, never `Déconnexion`, never
  `Accepter la quête`. A button is an action, and its label is that action.
- **A pending button keeps its verb** and adds an ellipsis: `Entrer` becomes `Entrer…`, not
  `Connexion…`. The label must not change identity halfway through a click.
## 1.1 An icon says what it does, before it is clicked

**Every icon-only control shows a tooltip on hover**, and on keyboard focus. This holds
everywhere, not just in a list: an icon is a guess until it is named, and a person should never
have to click one to find out.

The tooltip carries **the verb alone** — `Terminer`, `Consulter`, `Supprimer`. What the action
applies to goes into the spoken label instead: `IconButton` takes `label` for the tooltip and an
optional `subject`, and assembles `Terminer « Ranger le garage »` for a screen reader. Short to
look at, complete to listen to.

The website draws its own tooltip rather than using the native `title` attribute, which arrives a
second late and in the browser's chrome instead of the System's. The back-office uses Ant's
`Tooltip` the day it gains an icon-only control; it has none today, its buttons carry words.

### The icons, and what each one means

Icons come from **lucide-react** on both fronts. An icon means one thing across the whole
app — the same action never wears two faces, and two actions never wear the same one.

| Icon | Action | Note |
| --- | --- | --- |
| `Check` | terminer, cocher | |
| `RotateCcw` | rouvrir | the undo of `Check`, and only that |
| `Eye` | consulter | opens something read-only |
| `Pencil` | modifier | |
| `Plus` | créer, ajouter | |
| `Trash2` | supprimer | **always `variant="danger"`**, red from the start |
| `X` | fermer | **closing only** — never deleting |
| `ChevronRight` / `ChevronDown` | déplier / replier | carries `aria-expanded` |

The last two rows are the ones worth spelling out. A cross that deletes sits a few pixels
from a cross that closes, and the difference is discovered by clicking. So the bin deletes,
the cross closes, and nothing else does either.

**A destructive icon is red from the start**, not on hover: an action that cannot be undone
announces itself before it is reached, otherwise it looks like every other icon until it is
too late.

### An icon that is not an action

A **scale** is drawn with icons too — the five faces of the sleep mood — and those are not
actions: they name a value, not a verb, so the verb rule above does not apply to them. Everything
else does. Each level still carries a tooltip and a spoken label (`Bon réveil`), because an
unlabelled face is a guess exactly like an unlabelled verb.

Such a scale belongs in `RatingScale`, not in the feature that happens to need it first: picking
one level out of five, drawn as icons, with nothing chosen until someone chooses and a second
click taking the choice back. The faces themselves stay with the feature — the component knows
about levels, not about sleep.

## 2. Form actions

Every form ends with the same block, `FormActions`:

- **centred**, never pushed to one edge;
- **cancel first, then the verb that commits**, always in that order, so nobody has to read the
  buttons to know which is which;
- cancel is quiet, the committing verb carries the accent.

```tsx
<FormActions>
    <button type="button" className="button button-quiet" onClick={onDone}>Annuler</button>
    <button type="submit" className="button" disabled={pending}>Créer</button>
</FormActions>
```

## 3. Modals, and what cannot be undone

**An action that cannot be undone asks first.** Deleting a quest takes its sub-quests with it
and says so in the question; deleting a category says it is final. The website asks with
`ConfirmDialog`, the back-office with `ConfirmButton`; both word their buttons the same way.

The line is **what cannot be recreated in one gesture**, not what writes to the database.
Removing a hydration entry logged by mistake asks nothing: it is re-added with a single tap, and
a confirmation on every mis-tap would make correcting one worse than making it. A quest, its
sub-quests and a category are not retyped in a gesture, so they ask.


A form that is not the point of the screen opens in a modal, never inline: the screen shows its
content, and the form arrives over it.

- the page behind **blurs**, and does not scroll;
- **three ways out, all equivalent**: the cross at the top right of the window, a click outside
  it, and `Escape`. Cancel does the same thing;
- a click that *started* inside the window and ended outside does not close it, so dragging a
  selection out of a field is harmless;
- the window is mounted only while it is open, which is what resets the form between two uses.

## 3.1 Reading before writing

A window that shows a thing lists **every** field it has, filled in or not, each under its own
heading. A missing value says so — "Aucune échéance", in italics and dimmed — rather than
being left out: a reader who does not see the field cannot tell whether there is no value or
whether the app forgot one. `DefinitionList` does this on the website.

## 4. Nothing is selected by default

A creation form starts empty: no category, no tag, no preselected option in a list. Where a
default exists — the task priority — it is the **server** that applies it, once, on a payload
that named none. A front end never guesses on the person's behalf.

**The exception, and it is one:** a field that records a **measurement** opens on the last value
measured — the weight field is prefilled with the last known weight. It is not a guess among
options, it is the same quantity measured again: a weight moves by hundreds of grams, so the
previous number is almost the next one and retyping it in full is work for nothing. The test for
the exception is whether the field has a *previous value of its own* rather than a *likely
choice*; a category does not, and stays empty.

## 5. A page draws with components, never with the library

Both front ends keep everything they draw with in `src/components/`, re-exported from one
`index.ts`. The rule is deliberately checkable with a `grep`:

- **no page imports `antd`** — only `src/components/` does, plus `main.tsx` where the theme is
  configured. A screen reads in the vocabulary of the product, not of a widget set.
- **no page writes a `<button>`, an `<input>`, a `<select>` or a layout class by hand** on the
  website. `Button`, `IconButton`, `Select`, `Row`, `Stack`, `FormGrid` exist for that.

The point is not purity. It is that a decision — what a destructive action looks like, where a
form's buttons sit, how a date is written — gets made once and stays made. When Ant renames a
prop or the System gains a colour, one file changes.

Some components exist because a *shape* recurs, not a widget: `DataList` and `ListItem` on the
website draw any list of things — a row with an accent, a title, a progress note, a line of
chips, an action cluster, and children it folds away. They know nothing about tasks, and their
CSS says so: `.list-item`, not `.task`. The quest log and the category manager are both built
on them, which is what proves it.

Two components carry a rule in their type rather than in a comment:

- `IconButton` **requires** a `label`. An icon says nothing to a screen reader, and this is the
  only text it will ever get.
- `ConfirmButton` and `ConfirmDialog` own the wording of their two buttons, so no screen
  invents its own way of saying "Annuler".

Icons come from **lucide-react** on both fronts — the same set, so an action that means the
same thing looks the same in both.

## 6. Error wording belongs to the front end

The API answers with snake_case error codes, never sentences. Each front end words them its own
way, in one file (`src/api/violations.ts` on the website). Nothing else in the app writes an
error message.
