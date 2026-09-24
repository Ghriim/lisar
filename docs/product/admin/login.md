# Admin · Sign in

`/connexion` — the back-office's own door: `/api/admin/auth/*`, not the website's endpoints.

## An account belongs to one front end

**An administrator account opens the back-office and nothing else; a user account opens the
website and nothing else.** The API decides, not the page: a user signing in here is refused with
`wrong_audience`, and the page says so — the account exists, it is simply not an administrator.
The website refuses an administrator the same way.

It holds past the door, too. There is no role hierarchy: an administrator's token is refused on
the website's routes, a user's token on the back-office's. The only route both accept is
`GET /api/users/me`, since each front end needs to know who is signed in.

Someone who administers lisar and also uses it therefore has two accounts.

## The session is the back-office's alone

The back-office has **its own refresh cookie**, with its own name and path
(`admin_refresh_token`, `/api/admin/auth`). Both front ends talk to the same host and a browser
does not separate cookies by port, so with one shared cookie, signing in or out of one front end
signed in or out of the other. Now, with both open in the same browser, each keeps its own
session.

## Everything else is the website's behaviour

Session lifetimes, silent refresh, rotation with replay detection, the single answer for an
unknown e-mail or a wrong password, the dedicated answer for a deactivated account, signing out
everywhere: all of it is described in `../website/login.md` and none of it differs here. The
use cases are the same; only the audience they are called for changes.

## Not in v1

There is no password reset, forced or otherwise. An administrator locked out needs another
administrator, or the console command that creates one.
