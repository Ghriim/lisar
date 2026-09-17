# lisar — website

The application people actually use. React + TypeScript + Vite, with its own design: no
component library.

```
make start-website      # from the repository root: installs if needed, serves, opens the browser
```

The dev server listens on 5173 and proxies `/api` to the backend on 8080, so the browser sees a
single origin — which is what makes the httpOnly refresh cookie work in development.

## The design

A holographic status window, in the spirit of the System in *Solo Leveling*: deep navy, one
electric cyan carrying every interactive affordance, panels with a clipped corner and corner
ticks, monospaced small caps for anything the System itself says. The whole palette is declared
in `src/styles/tokens.css` and nowhere else.

## Layout

```
src/
├── api/          # the contract: types, the fetch client, the endpoints, the wording of errors
├── auth/         # the session: silent restore on load, sign in, sign out
├── components/   # the panel, the fields — the pieces every page is built from
├── features/     # the todo list: queries, the composer, a task row
├── pages/        # one per screen
└── styles/       # tokens first, then the global sheet
```

## How the session works

The access token lives in memory only — never in `localStorage`, never in a cookie this code can
read. The refresh token is an httpOnly cookie the browser sends back on its own.

- **On load**, the app asks `/api/auth/refresh` once. If the cookie is still good, it gets a
  token and the person never sees the login form.
- **On a 401**, the API client refreshes once and replays the request. A 15-minute token means
  this happens often, and it must stay invisible.
- **Signing out** drops every session of the account, on every device.
