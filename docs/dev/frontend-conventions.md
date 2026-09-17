# Front-end conventions

What holds across both front ends, `frontend/website` and `frontend/admin`. Anything specific to
one of them lives in its own README.

## 1. Buttons

- **A button is labelled with a verb, and with the verb alone.** `Créer`, `Enregistrer`,
  `Annuler`, `Se déconnecter` — never `Nouvelle quête`, never `Déconnexion`, never
  `Accepter la quête`. A button is an action, and its label is that action.
- **A pending button keeps its verb** and adds an ellipsis: `Entrer` becomes `Entrer…`, not
  `Connexion…`. The label must not change identity halfway through a click.
- An icon-only button still carries an `aria-label`, and there the verb may take an object
  (`Ajouter une sous-quête`): a screen reader has no icon to look at.

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

## 3. Modals

A form that is not the point of the screen opens in a modal, never inline: the screen shows its
content, and the form arrives over it.

- the page behind **blurs**, and does not scroll;
- **three ways out, all equivalent**: the cross at the top right of the window, a click outside
  it, and `Escape`. Cancel does the same thing;
- a click that *started* inside the window and ended outside does not close it, so dragging a
  selection out of a field is harmless;
- the window is mounted only while it is open, which is what resets the form between two uses.

## 4. Nothing is selected by default

A creation form starts empty: no category, no tag, no preselected option in a list. Where a
default exists — the task priority — it is the **server** that applies it, once, on a payload
that named none. A front end never guesses on the person's behalf.

## 5. Error wording belongs to the front end

The API answers with snake_case error codes, never sentences. Each front end words them its own
way, in one file (`src/api/violations.ts` on the website). Nothing else in the app writes an
error message.
