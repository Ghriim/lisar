# Work in progress — handoff note

Snapshot taken on 2026-10-23, to resume work from another machine. Claude's memory lives in
`~/.claude/` on the machine where it was written and does not travel with the repository: what it
held that the repository does not already say is copied below. Update this page, or delete it,
once it is stale.

## Where things stand

Updated on 2026-11-24.

- Domains delivered, back and front: user accounts and sessions, todo list, trackers (hydration,
  weight, sleep, steps), habits, workouts, routines (`docs/product/website/routines.md`) and
  personal bests. Each website and admin screen has its page under `docs/product/`.
- Routines, website side, built and type-checked but **not yet tried by hand in a browser**.
- **Current stream: planning** (`docs/product/website/planning.md`) — three kinds of planning
  entry, training, task and habit, one data model each, sharing one schedule shape.
  1. **Done**: the date engine `Domain/Planning/PlanningRule` (once, weekly every N weeks, N-th
     weekday of the month, fixed day of the month).
  2. **Done**: training entries, back and front — `TrainingPlanningEntryDataModel`,
     `/api/planning/training`, the `/planning` week page (`components/WeekCalendar`,
     `features/planning/`). A slot is kept by a workout of its routine started that day, read
     from the workouts, never stored. Not yet tried by hand in a browser.
  3. **Done**: habit entries, back and front — `HabitPlanningEntryDataModel` (hangs off the
     subscription), `/api/planning/habit`, the plus asking the kind, the habits panel dimming the
     days not planned (`HabitDayDataOutput::isPlanned`). States are read from the habit's days
     (`HabitPlanningCalendar`), never stored; unsubscribing deletes the entries through
     `HabitSubscriptionUpdatedEvent`. No run is counted anywhere yet: "a run counts planned days
     only" is for the gamification to apply. Not yet tried by hand in a browser.
  4. **Done**: task entries, back and front — `TaskPlanningEntryDataModel` (a task model on a
     rule), `/api/planning/task`, tasks linked back to their entry and date
     (`TaskDataModel::$planningEntry`, `$plannedDate`). A single date's task, or today's, is made
     on write (`SyncPlannedTasksEventHandler`); the next days' by the nightly
     `lisar:planning:create-tasks` (`#[AsCronTask]`, run by the `worker` container through
     symfony/scheduler on Messenger). The entry's time is the planning's only: the task has no
     time of day. Not yet tried by hand in a browser.
- The schedule fields are repeated on each entry data model (no Embeddable: that would be a new
  kind of component, not approved). `PlanningScheduleDataInput` / `PlanningScheduleDataOutput`
  are the shared shapes; `PlanningEntryDataModelFactory::applySchedule`,
  `PlanningScheduleOutputFactory` and `PlanningRule::datesOf` take any kind of entry (union type).

## Next steps

1. Try the planning by hand in a browser, the three kinds together.
2. Then, to settle with the user: the gamification (the "RPG" in *Life Is A RPG*), filters on
   `/todo`, routines shared from the back-office, filtering the planning by kind.

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
