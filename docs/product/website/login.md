# Website · Sign in

`/connexion` — the door, and where the session is born.

## What the page does

E-mail and password, nothing else. The e-mail is the sign-in identifier; the username is not.

On success the person lands on their quest log. There is a link to the sign-up page for whoever
has no account yet.

## Sessions

- **access token: 15 minutes**, held in memory on the front end only — never in `localStorage`,
  never in a cookie any script can read.
- **refresh token: 7 days**, in an `httpOnly` cookie the browser sends back on its own, scoped to
  the endpoints that spend it.
- **On load the app asks for a refresh once.** If the cookie is still good it gets a token and
  the person never sees this page at all.
- **On a 401 the front end refreshes once and replays the request.** A fifteen-minute token means
  this happens constantly, and it must stay invisible.

**Rotation with replay detection.** A refresh token is spent on use and replaced. If a spent one
comes back, either the person or a thief is holding a copy and there is no way to tell which — so
every live session of that account goes down and everyone signs in again.

## Refused sign-in

- **One single answer for an unknown e-mail and for a wrong password.** Telling them apart would
  tell a stranger which accounts exist.
- **An administrator account is refused** with `wrong_audience`, once the credentials have
  checked out: it belongs to the back-office only (see `../admin/login.md`). The website has its
  own refresh cookie, so signing in or out of the back-office in the same browser changes
  nothing here.
- **A deactivated account gets its own answer**, but only once the credentials have checked out,
  so the distinction leaks nothing. The person is told an administrator has to undo it; retrying
  will not help.
- **Credentials are taken exactly as typed** — neither the e-mail nor the password is trimmed.
  They are matched, not interpreted: editing what someone sent before comparing it would mean
  opening a session on something they did not type. Everywhere else in the API, surrounding
  whitespace is trimmed.

## Signing out

Signing out is offered from the quest log, not from here, and it **signs the account out
everywhere** — not just the device that asked. Signing out is what someone does when they suspect
they should, so it errs on the side of dropping too much.

## Deactivation takes effect at once

Even for someone holding a valid access token: the account is re-read on every call, and its live
sessions are dropped the moment an administrator deactivates it.

## Not in v1

The **forgot-password** journey. Someone locked out today needs an administrator.

## Open questions

- protection against repeated sign-in attempts
- expired session rows: purged on a schedule, or kept as a sign-in history?
