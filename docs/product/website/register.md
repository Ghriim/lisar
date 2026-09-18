# Website · Sign up

`/inscription` — sign-up is open, self-service, and immediately followed by a sign-in: asking for
the same credentials twice in a row would be rude.

## What the page asks for

| Field | Note |
| --- | --- |
| username | unique across lisar, and how the future friends list will address someone |
| e-mail | unique; **this is the sign-in identifier** |
| password | hashed on arrival, never readable back |

**Password policy: at least 8 characters, with a lowercase letter, an uppercase letter, a digit
and a special character.** Each class is checked separately, so the form says everything that is
missing at once rather than one thing per attempt. "Special" means anything that is neither a
letter nor a digit, spaces included.

## The account behind it

Beyond those three fields, an account carries an optional avatar, its last sign-in date, its
status (active or deactivated) and its role (user or administrator). The profile will grow with
the domains that follow — timezone, units of measure for the weight and hydration trackers,
language. None of that is asked for here.

**An account can be linked to several identities**: password today, Google and Apple later. Only
the password identity ships; the model provides for the others so adding them will not migrate
every account.

## Not in v1, deliberately

- **e-mail verification**: nothing is sent, nothing is confirmed
- **account deletion**, by the person or by an administrator
- **two-factor authentication**
- **Google / Apple sign-in** — modelled, not shipped

## Open questions

- can the username be changed afterwards? And the e-mail?
- avatar: a file to upload, or a URL?
