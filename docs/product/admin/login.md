# Admin · Sign in

`/connexion` — the same endpoint as the website, with one extra question asked at the door.

## The role is checked here, once

**A non-administrator is refused on this page**, with a message saying exactly that: the account
exists, it is simply not an administrator. The alternative — letting them in and having every
screen collect a 403 — tells them nothing and looks broken.

The refresh cookie is **shared with the website**, so a session restored on load belongs to
whoever last signed in on this browser. It is re-checked the same way: a restored session that is
not an administrator's is dropped rather than carried into the back-office.

`ROLE_ADMIN` implies `ROLE_USER`. An administrator is an account like any other, with their own
quest log; the role only adds this surface.

## Everything else is the website's behaviour

Session lifetimes, silent refresh, rotation with replay detection, the single answer for an
unknown e-mail or a wrong password, the dedicated answer for a deactivated account: all of it is
described in `../website/login.md` and none of it differs here.

## Not in v1

There is no password reset, forced or otherwise. An administrator locked out needs another
administrator, or the console command that creates one.
