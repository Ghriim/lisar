# Website · Navigation

The menu down the left edge of every signed-in screen. The screens reached before signing in —
login, register — have none.

## Two widths

- **Unfolded**: each entry shows its icon and its name.
- **Folded**: icons only. Each one then names itself in a tooltip on hover and on keyboard focus,
  like every icon-only control ([conventions](../../dev/frontend-conventions.md), §1.1).

A button at the top of the menu folds and unfolds it. The choice is remembered by the browser —
a preference of this device, nothing the account carries.

**On a narrow screen (720px and under) the menu is always folded**, and the button is gone: the
page needs the width more than the menu needs its words.

## What it holds

At the top, the name of the app and its subtitle — `LISAR`, *Life is a RPG*. Clicking it goes
to the dashboard.

Below it, aligned at the top — the screens the app is for:

| Entry | Icon | Route | Today |
| --- | --- | --- | --- |
| Dashboard | house | `/` | the [dashboard](dashboard.md) |
| Todo | list with a check | `/todo` | the [quest log](tasks.md) on its own |
| Workouts | dumbbell | `/workouts` | the [workouts](workouts.md): the one in progress, the history |
| Records | trophy | `/records` | the [personal bests](records.md) |
| Statistiques | chart | `/statistiques` | empty |

At the bottom — everything around them:

| Entry | Icon | Route | Today |
| --- | --- | --- | --- |
| Messages | letter | `/messages` | empty |
| Amis | two people | `/amis` | empty |
| Réglages | gear | `/reglages` | empty |
| Se déconnecter | exit | — | signs out, see [login](login.md#signing-out) |

The entry of the current screen carries the accent. Signing out is an action, not a place, so it
is worded with a verb like every button.

An **empty** screen already exists so the menu leads somewhere: it shows its title and says it is
coming. It gets its own page here the day it has something to show.
