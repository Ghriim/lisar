# Admin · Icons

`/developpeurs/icones` — every icon the fronts can draw, in one place.

## What it is for

A catalogue to browse, not reference data. It shows **every icon lucide-react ships**, not only
the ones lisar uses today: the point is to shop for the next one without leaving the app. It
reads nothing from the API and changes nothing.

Lucide is the icon set of both fronts (see `docs/dev/frontend-conventions.md` §1.1), so what is
shown here is what either front can import.

## Using it

- The search filters as you type, and matches both spellings: `ArrowUpRight`, the name to import,
  and `arrow-up-right`, the one on lucide.dev.
- **Clicking an icon copies its name**, ready to paste into an `import`.
- The count above the grid says how many icons match, out of how many there are.

## Why it loads on demand

The page imports the whole lucide set, several hundred kilobytes that no other screen needs. It is
split into its own chunk and fetched only when someone opens it.

## Not here

What each icon *means* in lisar. That is a convention, and it lives in
`docs/dev/frontend-conventions.md`, not on a screen that lists icons by the thousand.
