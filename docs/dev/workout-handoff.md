# Workout — handoff (2026-09-27)

Where the workout reference data stands, for whoever picks it up next. Delete this file once the
movement screen ships and `docs/product/admin/movements.md` exists.

## Done — backend + admin, tested

- **Equipments** (`/workout/equipements` in the admin): name unique ignoring case, `hasWeight`,
  `hasDistance`, active. Filters: status, load, distance. Delete **refused if a movement uses it**.
- **Muscle groups + muscles** (`/workout/muscles`, groups in a drawer on the same page). A muscle is
  in exactly one group, name unique across all groups. Inactive group: its muscles are not offered,
  takes no new muscle, a muscle already in it may stay. Group delete refused while not empty;
  muscle delete **refused if a movement targets it** (primary or secondary). Group `Other` holds
  `Cardio` and `Full body`.
- Product docs: `docs/product/admin/equipments.md`, `docs/product/admin/muscles.md` (their delete
  paragraphs still say "once movements exist … will be refused" — that is now true, reword).

## Done — backend only: movements and movement families

API under `/api/admin/workout/movements` and `/api/admin/workout/movement-families`: list, create,
update (PUT), activate, deactivate, delete — same shape as the muscle routes.

Decisions agreed with the user:

- A **movement**: name (unique among common movements, ignoring case — checked in the use case,
  **no DB index**, because a person's own movements will later be allowed a common name),
  optional description, optional video URL, exactly one **family**, one **primary muscle**, any
  number of **secondary muscles** (never the primary), zero or more **equipments** (none =
  bodyweight). Equipment variants are separate movements: "Bench press (barbell)" /
  "Bench press (dumbbell)", name typed in full.
- **What a set records**: four booleans `tracksReps`, `tracksWeight`, `tracksDuration`,
  `tracksDistance`; at least one of reps / duration / distance (weight only on top) —
  `MovementMeasureConstraint`. Plus `isUnilateral`.
- **Prefill (front, not done)**: on creation, choosing equipments ticks `tracksWeight` if one has
  `hasWeight`, `tracksDistance` if one has `hasDistance`; the admin adjusts. On edit, changing
  equipments unticks nothing.
- **Availability**: a movement takes on only active equipments and available muscles (active, in
  an active group) and an active family; what it already holds and was retired since may stay
  (same "staying is not moving" rule as muscles/groups).
- **Family**: name unique, active; required on every movement; same rules as muscle groups
  (inactive withdraws its movements, delete only when empty).
- **`owner`** column (nullable, null = common movement) exists for future custom user movements;
  nothing sets it yet, the admin only sees `owner IS NULL`.
- List filters: status, family, muscle group, muscle, equipment — **muscle and group match primary
  or secondary**. Not paginated.
- Deleting a movement is free for now; once workouts exist, refuse it if used.

Code: `MovementDataModel`, `MovementFamilyDataModel`, migration `Version20260927094059`,
`MovementRepository` (filters via subqueries so fetch-joined collections stay whole),
constraints `Movement*Constraint` + `*UnusedConstraint`, validators `Create/UpdateMovement*`,
use cases `UseCase/Admin/*Movement*`, controllers `AdminMovementController`,
`AdminMovementFamilyController`. Fixtures `MovementFamilyFixtures` (41) and `MovementFixtures`
(68, table readable in the file, measure letters R/W/T/D/U).

Tests: `tests/Integration/UseCase/Admin/MovementBackOfficeTest.php` (28). `make test` is green
(263 unit, 272 integration), PHPStan clean.

## To do next

1. **Unit tests** for the new constraints (`MovementMusclesConstraint`,
   `MovementEquipmentsConstraint`, `MovementMeasureConstraint`, `MovementFamilyUsableConstraint`,
   `MovementFamilyNameAvailableConstraint`, `MovementNameAvailableConstraint`, the three
   `*UnusedConstraint`) and validators (`Create/UpdateMovementValidator`,
   `Create/UpdateMovementFamilyValidator`) — same style as the existing `Workout` unit tests.
2. **Admin front** `/workout/mouvements`, modelled on `MusclesPage.tsx`:
   - menu entry under Workout in `frontend/admin/src/layout/AdminLayout.tsx`, route in `App.tsx`;
   - types + endpoints (`frontend/admin/src/api/`), error wording in `violations.ts` for:
     `movement_name_already_used`, `movement_family_name_already_used`, `movement_family_not_found`,
     `movement_family_inactive`, `movement_family_in_use`, `primary_muscle_not_found`,
     `primary_muscle_unavailable`, `secondary_muscle_not_found`, `secondary_muscle_unavailable`,
     `primary_muscle_also_secondary`, `equipment_not_found`, `equipment_inactive`,
     `measure_required`, `description_too_long`, `video_url_invalid`, `video_url_too_long`,
     `equipment_in_use`, `muscle_in_use`;
   - columns: name, primary muscle, secondary muscles, equipments, status; filters: `ActiveFilter`
     + `FilterSelect` for family, group, muscle, equipment; search on name;
   - form: name, description, video URL, family (select), primary muscle (select), secondary
     muscles and equipments (multi-selects), the four `tracks…` switches, unilateral. Needs a
     `multiple` option on `SelectField`, a text-area form field, and a way for `FormModal` to
     react to value changes (for the prefill);
   - families managed in a drawer on the same page, like muscle groups.
3. **Docs**: `docs/product/admin/movements.md` (the decisions above), README index, and reword the
   delete paragraphs of equipments.md / muscles.md.
4. Pre-existing and unrelated: the equipment and muscle pages show the 422 of a refused delete
   through `useNotifier` — check the wording once `equipment_in_use` / `muscle_in_use` are added.

Dev database: migrated, but workout tables are empty until `make load-fixtures` (reloads the whole
dev database).
