# Events raised by a write

What follows from a write — a record rebuilt because a set was ticked — is not written in the
persister that made the write, nor in the use case that called it. **The persister raises an
event; handlers react to it, synchronously, in the same transaction.** This page is the
convention; `RefreshPersonalBestsEventHandler` is the first handler built on it.

These are *internal* events. The inbound bus of the blueprint's §6.16 is another thing: events
from outside, named by versioned strings and validated against a schema. Nothing here crosses the
process.

## The pieces

| Piece | Where | What it is |
| --- | --- | --- |
| event | `Domain/DTO/Event/<Perimeter>/<Thing><Past>Event.php` | `final readonly`, implements `EventInterface`, carries data models |
| dispatcher contract | `Domain/Event/EventDispatcherInterface.php` | `dispatch(EventInterface)` |
| dispatcher | `Infrastructure/EventHandler/SynchronousEventDispatcher.php` | runs every handler of the event, in order, before returning |
| handler contract | `Infrastructure/EventHandler/EventHandlerInterface.php` | tagged `app.event_handler`; `getSupportedEvents()` (static) and `handle()` |
| handler | `Infrastructure/EventHandler/<Perimeter>/<Action>EventHandler.php` | reads through provider gateways, writes through persister gateways |

Implementing `EventHandlerInterface` is the whole registration: the interface carries the tag, and
the dispatcher indexes every tagged handler by the event classes it declares. Several handlers may
listen to one event; none may count on running before another.

## The rules

- **An event is named for what happened, in the past**: `WorkoutSetUpdatedEvent`, never
  `RefreshRecordsEvent`. The event does not know who listens.
- **It is raised by the persister, after its flush**, so every write reaches it — a use case, a
  fixture, a command — without anyone remembering to.
- **It carries what its handlers need, read before the write when the write destroys it.** A
  deleted set no longer knows its owner: `WorkoutSetDeletedEvent` takes the owner and the movement
  while they are still there.
- **The write and its handlers are one transaction.** The persister runs both inside
  `AbstractBaseMysqlPersister::inTransaction()`; a handler that throws rolls the write back with it,
  so a write never stands without what follows from it.
- **A handler's own writes raise nothing.** A persister used by handlers only — the personal bests'
  — does not dispatch; a chain of handlers triggering handlers is how a request ends up doing
  everything twice.
- **A handler holds no business rule it could hand to the domain.** It gathers, calls a domain
  factory or service, and writes. The rule — what beats a record — is unit-tested in the domain.
- **An event that only matters for some changes says so**: `SetTypeUpdatedEvent` carries whether
  `countsForPersonalBests` changed, read from Doctrine's original data before the flush, and the
  handler skips the rest.

## What exists

| Event | Raised by | Handled by |
| --- | --- | --- |
| `WorkoutSetCreatedEvent` · `WorkoutSetUpdatedEvent` · `WorkoutSetDeletedEvent` | `WorkoutSetPersister` | `RefreshPersonalBestsEventHandler` |
| `WorkoutExerciseDeletedEvent` | `WorkoutExercisePersister::delete` | `RefreshPersonalBestsEventHandler` |
| `WorkoutBlockDeletedEvent` | `WorkoutBlockPersister::delete` | `RefreshPersonalBestsEventHandler` |
| `WorkoutUpdatedEvent` · `WorkoutDeletedEvent` | `WorkoutPersister` | `RefreshPersonalBestsEventHandler`, `SyncWorkoutHabitsEventHandler` |
| `SetTypeUpdatedEvent` | `SetTypePersister::update` / `updateMany` | `RefreshPersonalBestsEventHandler` |
| `HydrationEntryCreatedEvent` · `HydrationEntryUpdatedEvent` · `HydrationEntryDeletedEvent` | `HydrationEntryPersister` | `SyncHydrationHabitsEventHandler` |
| `StepDayCreatedEvent` · `StepDayUpdatedEvent` | `StepDayPersister` | `SyncStepHabitsEventHandler` |

### The habits a tracker keeps

The three `Sync<Tracker>HabitsEventHandler` each work out their tracker's figure for a day — a
day's hydration total, its step count, how many workouts were finished on it — and hand it to
`AbstractTrackerHabitsEventHandler::keep()`, which keeps or unkeeps every active subscription
watching that tracker. Whether a figure keeps a day is `HabitEntryDataModelFactory`'s rule. A
tracker knows nothing of habits: it writes, and its persister says so.

**A persister keeps both sides of an association in step before it dispatches.** A handler reads
what the write left in memory as much as what it left in the database: `HydrationEntryPersister`
adds a new entry to its day's `entries`, and takes a removed one out, before the hydration
handler reads the day's total off them.
