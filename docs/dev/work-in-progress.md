# Work in progress — handoff note

Snapshot taken on 2026-10-23, to resume work from another machine. Claude's memory lives in
`~/.claude/` on the machine where it was written and does not travel with the repository: what it
held that the repository does not already say is copied below. Update this page, or delete it,
once it is stale.

## Where things stand

- Branch `main`, clean, in sync with `origin/main` (last commit `af7c3a4 Optimization of personal best`).
- Domains delivered, back and front: user accounts and sessions, todo list (tasks, categories),
  trackers (hydration, weight, sleep, steps), habits, workouts (movements, equipments, muscles,
  set types, blocks/exercises/sets, rest, copy of a workout, summary stored in the database,
  personal bests). Each website and admin screen has its page under `docs/product/`.
- **Last stream of work (2026-10-14 → 2026-10-16): performance pass.**
  - Validation rules refactored: one validator per use case, calling its constraints one by one
    (`IsOwnerConstraint`, `IsAdminConstraint`, `…InWorkoutConstraint`…).
  - Priorities and tags removed from the todo list (migrations `Version20261015*`).
  - Query budgets: every one of the 111 use cases has an integration test asserting
    `assertQueryCountAtMost` / `assertQueryCountDoesNotGrow` (`tests/Integration/QueryCountTrait.php`,
    `config/packages/query_count.yaml`). Integration tests record events instead of running their
    handlers (`config/packages/event_recording.yaml`).
  - Timestamps moved to a Doctrine listener (`src/Infrastructure/Persister/TimestampListener.php`).
  - Personal bests refreshed incrementally from the workout events (`RefreshPersonalBestsEventHandler`).
  - The pass looks complete; nothing was left half-done in the working tree.

## Next steps

Not decided yet — to settle with the user at the start of the next session. Candidates from the
announced scope:

- **Task planning** — announced in the original scope, no product page yet.
- **The gamification itself** (the "RPG" in *Life Is A RPG*): nothing yet rewards what the person
  does across domains.
- Filters on `/todo` (announced in `docs/product/website/tasks.md`).

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
