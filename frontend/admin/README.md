# lisar — admin

The back-office: accounts, and the reference data everyone else picks from.

React + TypeScript + Vite, built with **Ant Design**. It draws nothing of its own — that is the
point of using a component library here. The only deviation from Ant's defaults is the dark
algorithm and the family's accent colour, set once in `main.tsx`.

```
make start-admin        # from the repository root: installs if needed, serves, opens the browser
```

The dev server listens on 5174 and proxies `/api` to the backend on 8080, so the browser sees a
single origin — which is what makes the httpOnly refresh cookie work in development.

## What it does

| Screen | What it holds |
| --- | --- |
| Comptes | the account list, searchable and filterable by status, with deactivation and reactivation; a drawer per account showing its metadata and its internal note thread |
| Priorités | the priority set: label, weight, colour, and which one is the default |
| Catégories | the common categories, the ones every account picks from |
| Icônes | every lucide icon, searchable, a click copying its name — a developer's catalogue, not data |

**It never shows the contents of an account.** A person's tasks are not the back-office's
business, and the API would not serve them here anyway.

## Getting in

The back-office signs in through `/api/admin/auth/*`, with a refresh cookie of its own: signing in
or out here leaves the website's session alone. The API refuses a non-administrator **at the
door** (`wrong_audience`), and the page says why.

## Conventions

Buttons and forms follow `docs/dev/frontend-conventions.md`: a verb and nothing but the verb,
actions centred, cancel first. Ant's default modal footer is replaced for that reason.
