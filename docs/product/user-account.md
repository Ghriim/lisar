# User account & authentication

The cross-cutting domain every other one depends on: lisar is multi-account and a user's
data is strictly private to them. This domain is built **before** the todo list.

## 1. Scope

**In v1**

- open self-service sign-up
- sign-in with e-mail + password
- short-lived session with silent refresh
- two roles: user and administrator; the first administrator is created by a console command
- back-office: account lookup, deactivation / reactivation, comments
- profile: username, e-mail, avatar, last sign-in date

**Out of scope for v1**

- e-mail address verification at sign-up
- the "forgot password" journey
- password reset forced by an administrator
- two-factor authentication
- account deletion, by the user or by an administrator
- Google / Apple sign-in: **modelled now, not shipped** (see §3)

## 2. The account

| Field | Note |
| --- | --- |
| username | entered at sign-up; unique (useful for the future friends list) |
| e-mail | unique; **this is the sign-in identifier** |
| password | hashed, never readable back |
| avatar | optional |
| last sign-in date | updated on every successful sign-in |
| status | active or deactivated |
| role | user or administrator |

The profile will grow with the domains that follow (timezone, units of measure for the
weight and hydration trackers, language…). None of that is required in v1.

## 3. Sign-in methods

An account can be linked to **several identities**: password, Google, Apple. Only the
password identity ships in v1; the model provides for the others from the start so it
does not have to be reworked later.

## 4. Session

- access token valid for **15 minutes**
- refresh token valid for **7 days**, renewed silently
- technical proposal, adopted for lack of a stated preference: refresh token rotation,
  stored in an `httpOnly` cookie; the access token stays in memory on the front end and is
  never written to browser storage

## 5. Roles and back-office

**Two roles only**: user and administrator. The **first administrator is created by a
console command**; there is no way to sign up as an administrator.

What an administrator can do to an account:

- **look it up** (account metadata only)
- **deactivate** and **reactivate** it
- **leave comments** on it

What they cannot do: **see the contents of an account**. A user's tasks are never exposed
to the back-office.

### Deactivated account

Sign-in is refused with a dedicated message. **All data is kept** and an administrator can
reactivate the account at any time.

### Administrator comments

A comment thread attached to an account, for internal follow-up (support, moderation).
Each comment carries its author and its date. **Visible from the back-office only**: never
exposed to the user concerned, nor through the website API.

## 6. Settled along the way

- **Password policy**: at least 8 characters, with at least one lowercase letter, one uppercase
  letter, one digit and one special character. Each class is checked separately, so the caller
  is told every missing one at once rather than one per attempt.
- **Violation vocabulary**: the API answers with snake_case error codes (`email_already_used`,
  `password_too_short`), never English sentences — both front ends read the same vocabulary.
- **Sign-out signs the account out everywhere**, not just on the device that asked. Signing out
  is what someone does when they suspect they should, so it errs on the side of dropping too
  much: every live session of the account goes down.
- **Refused sign-in**: one single answer for an unknown e-mail and for a wrong password, so that
  a stranger cannot discover which accounts exist. A deactivated account gets its own answer, but
  only once the credentials have checked out.
- **A replayed refresh token kills the account's sessions.** Rotation means a refresh token is
  spent on use; if a spent one comes back, either the person or a thief is holding a copy and we
  cannot tell which, so every live session goes down and everyone signs in again.
- **Deactivation takes effect immediately**, even for someone holding a valid access token: the
  account is re-read on every call, and the account's live sessions are dropped on the spot.
- **An administrator cannot deactivate their own account.** They would lock themselves out of the
  back-office, and only another administrator — or the console command — could undo it.
- **Administrator notes are attributed and immutable**: each one carries its author and its date,
  the thread reads newest first, and nothing edits or deletes a note for now.
- **Lists are paginated with a hard cap** (25 per page by default, 100 maximum) and answer with
  the same envelope everywhere: `items`, `total`, `page`, `perPage`.

## 7. Open questions

- can the username be changed after sign-up? And the e-mail?
- avatar: file upload or plain URL?
- sign-in brute-force protection (attempt rate limiting)
- expired session rows: are they purged on a schedule, or kept as a sign-in history?
- administrator notes: should they be editable or deletable at all, and by whom? They are
  append-only today.
- the account list: any other filter the back-office needs (role, signed in recently, never
  signed in)?
