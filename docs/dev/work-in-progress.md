# Work in progress — handoff note

Snapshot taken on 2026-10-23, to resume work from another machine. Claude's memory lives in
`~/.claude/` on the machine where it was written and does not travel with the repository: what it
held that the repository does not already say is copied below. Update this page, or delete it,
once it is stale.

## Where things stand

Updated on 2026-11-23.

- Domains delivered, back and front: user accounts and sessions, todo list (tasks, categories),
  trackers (hydration, weight, sleep, steps), habits, workouts (movements, equipments, muscles,
  set types, blocks/exercises/sets, rest, copy of a workout, summary stored in the database,
  personal bests). Each website and admin screen has its page under `docs/product/`.
- **Current stream: routines** (`docs/product/website/routines.md`) — the blueprint of a workout,
  each set prescribed as reps and a share of the estimated 1RM.
  - **Back-end done**: schema, editing use cases (list, create, read, rename, delete, blocks,
    movements, sets, reorder), `POST /api/routines/{id}/start` (a workout laid out like the
    routine, loads worked out by `PrescribedLoad`, linked through `workout.routine_id`, exposed
    as `routineId` on a workout), `POST /api/routines/{id}/copy` (« Copie de … »).
  - **Front-end done**: the « Routines » tab of the card on `/workouts`, the `/routines/:id`
    page (`frontend/website/src/features/routines/`), the « Routine » link on a workout started
    from one. Type-checked and built; not yet tried by hand in a browser.

## Next steps

1. Try the routine screens by hand, and adjust.
2. Then, to settle with the user: task planning (announced, no product page yet), the
   gamification itself (the "RPG" in *Life Is A RPG*), filters on `/todo`, routines shared from
   the back-office (listed under « Not here yet » in `routines.md`).

## Context Claude needs that the repository does not hold

- **lisar = *Life Is A RPG***: gamification of daily life (todo list, workouts, trackers, habits,
  task planning).
- **Claude plays Product Owner**: the user wants to be *interviewed* to define features, in small
  batches of targeted questions, and decides. Never hand over a ready-made spec.
- **Iterative, one domain at a time**: interview → page in `docs/product/` → development → next
  domain. A domain is not coded before its product page exists.
- **Documentation in English** (file names too), even when the conversation is in French;
  `docs/` is fed as features land, not afterwards.
- **Never edit Symfony skeleton files** (`src/Kernel.php`, `tests/bootstrap.php`,
  `public/index.php`, Flex recipe files): configure the tool instead (`excludePaths` in
  `phpstan.neon`, `notPath()` in `.php-cs-fixer.dist.php`).
- `AGENTS.md` from the Flex recipe was deleted on purpose: `docs/dev/architecture/backend-architecture.md`
  is the only code convention. Do not bring it back.
- **Website visual direction**: the "System" from *Solo Leveling* — very dark blue background,
  light high-contrast text, one electric cyan for everything interactive, bevelled panels, small
  monospace capitals for the System's voice. Palette only in `frontend/website/src/styles/tokens.css`;
  reuse `SystemPanel`, `Field` and `styles/global.css`. The user said "we'll adjust": it may evolve,
  but is not to be reinvented per screen.
- **Website UI in French**; code, comments, docs and API error codes in English. Error wording only
  in each front's `src/api/violations.ts`.
