# Admin · Accounts

`/comptes` — the list of accounts, and what can be done to one.

## What the page shows

A paginated table: username, e-mail, status, last sign-in, with the administrators marked. Free
text search over **username and e-mail**, and a filter on status — all, active, deactivated.

Opening an account shows a drawer: its metadata, and its note thread.

## What it never shows

**The contents of an account.** Tasks, personal categories, tags: none of it is served to this
surface, and no screen asks for it. The rule is enforced by the API, not by a front end
remembering not to display it.

## Deactivating and reactivating

- **Deactivating drops the account's live sessions on the spot.** An access token stays
  cryptographically valid for up to fifteen minutes; leaving the sessions alive would leave a
  deactivated person working.
- **All data is kept.** Reactivating puts the account back exactly as it was, minus the sessions
  it had.
- **An administrator cannot deactivate their own account.** They would lock themselves out of
  this surface, and only another administrator — or the console command — could undo it.

## Internal notes

A thread attached to an account, for support and moderation. Each note carries its author and its
date, newest first.

**They are invisible to the account they are about**, and not served by the website's API at all.
They are **append-only**: nothing edits or deletes a note today.

## The first administrator

Nothing on the HTTP surface grants `ROLE_ADMIN` — there is no "make this account an
administrator" button, by design. The first one is created by the console command
`lisar:user:create-admin`, which asks for the password rather than taking it on the command line,
so it stays out of the shell history and the process list.

## Open questions

- creating an account from here, and forcing a password reset — both were left out of the account
  domain's first version
- should notes ever be editable or deletable, and by whom?
- any further filter worth having: role, signed in recently, never signed in
