# Website · Habits

A panel on the dashboard: the habits the person has taken on, each with the last week at a glance
and — when it is theirs to tick — a button to mark today done.

Habits are not tasks. A task is done once and gone; a habit is never done, it is kept, one day
at a time, and what matters is the run of days, not a finish line. It is closer to the trackers
beside it than to the list below: a per-day record against a recurring intent.

## Subscribing

The habits themselves are a **catalogue an administrator maintains** (see the back-office page).
The person does not create habits yet — they **subscribe** to the ones they want to keep, from a
window opened off the panel, and unsubscribe from the ones they drop. The window groups them
**under their category** (flat while none has one).

The window is tall and fixed: **a search** sits on top — by name or category, accents and case
aside — and only the list under it scrolls. Each habit says how it is kept — **« Manuel »** or
**« Automatique »** — and, once kept, **the days it counts** (« Lun, mer, ven »). Taking it on is
the **plus** (« Suivre »), dropping it the **minus**, red (« Retirer »).

**That window is also the one place a habit's planning is managed.** « Suivre » takes the habit on
and unfolds, under its line, the form of the « tous les jours » it starts with: the person says
there and then which days it counts — or keeps every day and leaves. A kept habit carries
**« Planifier »** (a calendar), which unfolds the same under its line: its entries one per line,
each to change (the pencil) or delete (the bin — absent on the last one: a kept habit always has a
planning), and « Ajouter » for another. The form is the planning page's, the habit already
chosen.

Unsubscribing **keeps the history**: it stops the habit appearing in the list and stops its run
counting, but the days already kept are not erased — the day habits earn experience, a dropped-then-resumed
habit must not have had its past wiped.

## The panel

**Only the habits planned for today are listed** — the ones planned today, and those with no
planning at all, which count every day. A habit whose planning skips today — « Lire » on Monday,
Wednesday and Friday, on a Thursday — is not on the panel that day; it is still in the catalogue
and on the planning. Which habits these are is the API's to say: the panel reads
`GET /api/habits/today`, never the whole list. When none is planned today, the panel says
« Aucune habitude prévue aujourd’hui. ».

The habits are **one flat list**, without category headings: a habit's category is the small icon
on its line. A habit's category is the catalogue's — a common one, set in the back-office — and the
person does not change it.

The section is titled `[ HABITUDES ]`, outside its card, with **« Gérer les habitudes »** on the
right, which opens the catalogue.

One box per subscribed habit, in two parts: on top, the name with what it counts under it on the
left and its tick on the right; below, across the full width, **its last seven days**.

- **its name**, with its category's **small icon** just before it on the same line — the category's
  label is not shown, the icon names it on hover; a habit without a category has none;
- under it, dimmed, **the days it counts**, short — « Lun, mer, ven », « Tous les jours », several
  entries one after the other — then **its run**: « série 4 ». No run is counted yet, so it reads
  « série — » (`features/habits/habitStreak.ts`). Every kept habit has a planning
  ([planning](planning.md#a-habit-entry)): taken on, it counts every day;
- **the last seven days, today always last**, oldest on the left: each day's initial (L, M, J…),
  today's in the accent, over a 22px dot —

  | The day | Its dot |
  | --- | --- |
  | kept (planned or not) | a filled disc, the accent |
  | missed | a red circle with a small cross |
  | today, planned, still to do | an accent circle, 2px, with a glow |
  | not planned | a short dash |

- **« Valider aujourd'hui »**, and only when the habit is **manual**: a box, outlined in the accent,
  filled with a dark check once today is kept. The same box unticks it. Ticking it fills today's dot.

A habit **fed by a tracker** carries no box: in its place, a dashed square with a sync icon —
« Validée automatiquement » on hover, not a control. It is kept on its own the moment the tracker
it watches crosses its mark (10000 steps, 1500 mL, one workout finished); its week fills in by the
tracker rather than by a tap. Deleting the day's only finished workout unkeeps the day again.

## The rules

- **The current day, and nothing else.** A habit is kept for the day in progress; a day missed is
  missed, and no past circle can be filled in after the fact. The back end never names a day.
- **A day starts at midnight in `Europe/Paris`**, for everyone — the placeholder every tracker
  shares.
- **Keeping is binary.** Today is kept or it is not; there is no half. A habit whose progress is a
  quantity — a count toward a target — is fed by a tracker, not typed here. (Typing a count by
  hand is a later addition; the model allows it, the screen does not offer it yet.)
- **Only what you subscribed to shows.** The list is the person's own habits, not the catalogue;
  the catalogue is reached through the subscribe window.

## Not in v1

- creating one's own habits (the catalogue is admin-only for now);
- "N times a week" habits (which days a habit counts is set on the [planning](planning.md#a-habit-entry),
  every day by default);
- typing a manual count toward a target;
- the experience a kept habit will earn, and the streak bonus that rewards a long run — a separate
  gamification domain will read what is being recorded now: each kept day, the moment it was kept,
  and how many days it has run.

## Midnight, with the page still open

As with the trackers, **no request names a day**: keeping a habit lands on the day the server
counts as in progress. The whole page goes stale when the day turns and reloads itself — the habit
panel inherits that watch, it does not add its own.
