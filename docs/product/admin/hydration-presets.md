# Admin · Hydration shortcuts

`/hydratation/raccourcis` — the quantities people log in one tap.

## What a shortcut is

An **icon** and a **volume**. Nothing else: no label, because the icon and the volume say it —
a glass at 250 mL needs no word, and the word would have to be translated by each front end
anyway.

The icon is picked from a fixed vocabulary — glass, bottle, mug, can, carafe — stored as a code.
Each front end draws it with its own icon set, which is what keeps the back-office from shipping
images.

They are listed smallest volume first, which is the order they are offered in.

The vocabulary today: `glass`, `bottle`, `mug`, `can`, `carafe`.

## Deleting one is safe, and that is deliberate

Unlike a priority or a common category, **a shortcut can always be deleted**, even after years of
use. An entry copies the volume it logged rather than pointing at the shortcut, so removing
"bottle · 500 mL" removes a button and nothing else. Nobody's past changes.

This is the whole reason the copy was chosen over a relation, and it is worth remembering before
anyone "normalises" it.

## Not here

The daily goal. It is 1500 mL for everyone, set as a parameter of the application rather than
data, because there is nothing yet to vary it by. When a personal goal arrives it will live on
the account, not in this reference set.

## Open question

Whether shortcuts should be orderable by hand, as priorities are by weight, rather than sorted by
volume.
