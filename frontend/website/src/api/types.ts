/** The API contract, transcribed. Every shape here mirrors a DataOutput on the back end. */

export interface User {
    id: number
    username: string
    email: string
    avatar: string | null
    isActive: boolean
    lastSignedInAt: string | null
    createdAt: string | null
    updatedAt: string | null
}

export interface Session {
    accessToken: string
    expiresIn: number
    tokenType: string
}

/**
 * The task priorities, mirroring the backend registry, highest first. Kept in sync by hand: the
 * code is the contract, the word and the colour are this front end's business.
 */
export const TASK_PRIORITIES = ['high', 'normal', 'low'] as const

export type TaskPriority = (typeof TASK_PRIORITIES)[number]

export interface Category {
    id: number
    label: string
    isPersonal: boolean
}

export type TaskState = 'to_do' | 'in_progress' | 'ready_to_close' | 'done'

export interface Task {
    id: number
    title: string
    description: string | null
    /** A calendar day, YYYY-MM-DD. */
    dueDate: string | null
    state: TaskState
    priority: TaskPriority | null
    category: Category | null
    subtasks: Task[]
    parentId: number | null
    completedAt: string | null
    createdAt: string | null
    updatedAt: string | null
}

export interface CreateTaskPayload {
    title: string
    description?: string | null
    dueDate?: string | null
    priority?: TaskPriority | null
    categoryId?: number | null
    parentId?: number | null
}

export type UpdateTaskPayload = Omit<CreateTaskPayload, 'parentId'>

/**
 * The API answers a rejected payload with one error code per field, never a sentence: the
 * wording belongs to the front end.
 */
export type Violations = Record<string, string[]>

export interface HydrationPreset {
    id: number
    /** One of the codes the API knows; each front end draws and words it its own way. */
    icon: string
    volumeInMillilitres: number
}

export interface HydrationEntry {
    id: number
    volumeInMillilitres: number
    recordedAt: string | null
}

/**
 * The night of the day in progress. Everything but the day is null when nothing has been noted
 * this morning — which is the normal first state of every day, not an error.
 */
export interface SleepNight {
    /** The waking day, YYYY-MM-DD, in the timezone the app counts days in. */
    day: string
    /** The instant one went to bed — the evening before, when the night crossed midnight. */
    bedtimeAt: string | null
    wakeUpAt: string | null
    durationInMinutes: number | null
    /** How one felt on waking, 1 to 5, or null for a night one did not rate. */
    moodRating: number | null
}

/**
 * The last known weight: today's if there is one, otherwise the most recent day's. Every field
 * is null for someone who has never weighed themselves.
 */
export interface Weight {
    weightInKilograms: number | null
    /** A calendar day, YYYY-MM-DD, in the timezone the app counts days in. */
    day: string | null
    recordedAt: string | null
    /**
     * Whether that weight is today's, and therefore whether recording corrects it or creates
     * one. The browser cannot work this out: the timezone days are counted in belongs to the API.
     */
    isFromToday: boolean
}

export interface HydrationDay {
    /** A calendar day, YYYY-MM-DD, in the timezone the app counts days in. */
    day: string
    goalInMillilitres: number
    totalInMillilitres: number
    entries: HydrationEntry[]
}

/**
 * The step count of the day in progress. `day` and `goalInSteps` are always there — a day always
 * has a goal. `countInSteps` and `source` are null until the first save of the day, and that null
 * is not a zero: zero is a day recorded as having no steps, null is a day nothing was recorded on.
 */
export interface StepDay {
    /** A calendar day, YYYY-MM-DD, in the timezone the app counts days in. */
    day: string
    goalInSteps: number
    countInSteps: number | null
    /** Which writer set the count — a code the API knows — or null on an untouched day. */
    source: string | null
}

export type HabitSource = 'manual' | 'tracker'

/** One of the seven days a habit's line shows. */
export interface HabitDay {
    /** A calendar day, YYYY-MM-DD. */
    day: string
    isCompleted: boolean
    /** Whether the habit's planning asks for the day: every day for a habit without any entry. */
    isPlanned: boolean
}

/** A subscribed habit, as its line on the panel. */
export interface Habit {
    habitId: number
    name: string
    /** One of the codes the API knows; this front end draws its own glyph for it. */
    icon: string
    /** A manual habit is ticked by hand; a tracker habit is kept on its own. */
    sourceKind: HabitSource
    isCompletedToday: boolean
    /** Last seven days, oldest first, today last. */
    days: HabitDay[]
}

/** One catalogue habit in the subscribe window. */
export interface HabitCatalogItem {
    habitId: number
    name: string
    icon: string
    sourceKind: HabitSource
    trackerKind: string | null
    trackerThreshold: number | null
    isSubscribed: boolean
}

/** A set type from the back-office list: a name, and a colour code this front end paints. */
export interface SetType {
    id: number
    name: string
    /** One of the palette codes the API knows — red, orange… — never a hex value. */
    colour: string
    isActive: boolean
    /** The one a set takes when it is logged without one: the ordinary working set. */
    isDefaultType: boolean
}

/** A movement as a workout picker offers it: only what a set needs to know about it. */
export interface WorkoutMovementChoice {
    id: number
    name: string
    movementFamilyId: number
    movementFamilyName: string
    tracksReps: boolean
    tracksWeight: boolean
    tracksDuration: boolean
    tracksDistance: boolean
    isUnilateral: boolean
}

/** A movement as a workout carries it — possibly retired since. */
export interface WorkoutMovement {
    id: number
    name: string
    tracksReps: boolean
    tracksWeight: boolean
    tracksDuration: boolean
    tracksDistance: boolean
    /** One set covers both sides, and its reps count per side. */
    isUnilateral: boolean
    isActive: boolean
}

/** A set carries exactly the measures its movement tracks; the others are null. */
export interface WorkoutSet {
    id: number
    /** Always there: an ordinary working set carries the default type. */
    setType: SetType
    reps: number | null
    weightInKilograms: number | null
    durationInSeconds: number | null
    distanceInMetres: number | null
    /** 1 to 10, by halves. */
    rpe: number | null
    /**
     * Ticked as done. A set is logged before it is done, and ticked once it is; a finished
     * workout holds only done sets.
     */
    isComplete: boolean
    /** The personal bests this set beat, in the order records are listed in. */
    personalBests: PersonalBest[]
}

/** A personal best, the moment it was beaten. */
export interface PersonalBest {
    id: number
    /** A code the API knows — max_weight, best_pace… — worded in personalBestFormat. */
    kind: string
    /** A number of reps, a distance in metres or a load, for a tiered kind; null otherwise. */
    tier: number | null
    /** In the kind's unit: kilograms, reps, seconds, metres, seconds per kilometre. */
    value: number
    /** Null for a record of a whole workout. */
    movementId: number | null
    movementName: string | null
    workoutId: number
    /** Null for a record a whole workout beat. */
    setId: number | null
    /** When the workout that beat it started. */
    achievedAt: string
}

/** One record and every time it was beaten, oldest first: the last is `current`. */
export interface PersonalBestRecord {
    kind: string
    tier: number | null
    current: PersonalBest
    progression: PersonalBest[]
}

export interface MovementPersonalBests {
    movementId: number
    movementName: string
    movementFamilyId: number
    movementFamilyName: string
    /** False for one retired since, listed because it holds records. */
    isOffered: boolean
    records: PersonalBestRecord[]
}

/** Every record of the account: those of whole workouts, then movement by movement. */
export interface PersonalBestBoard {
    sessions: PersonalBestRecord[]
    /** Every movement on offer, records or not, by family then by name. */
    movements: MovementPersonalBests[]
}

export interface WorkoutExercise {
    id: number
    movement: WorkoutMovement
    note: string | null
    /** The rest planned after each of its sets, which the timer counts down. Null: no timer. */
    restInSeconds: number | null
    /** In the order they were logged. */
    sets: WorkoutSet[]
}

/** One movement of a block being added, with its own rest. */
export interface WorkoutBlockExercisePayload {
    movementId: number
    restInSeconds: number | null
}

/** One movement, or several done back to back: a superset. */
export interface WorkoutBlock {
    id: number
    exercises: WorkoutExercise[]
}

export interface Workout {
    id: number
    /** Null when none was given: the front words a default. */
    name: string | null
    note: string | null
    /** The overall feeling, 1 to 5. */
    feeling: number | null
    startedAt: string
    finishedAt: string | null
    isInProgress: boolean
    /** The routine it was started from, while that routine exists. */
    routineId: number | null
    blocks: WorkoutBlock[]
}

/** A workout started from a past one or from a routine, and what it could not take over. */
export interface WorkoutCopy {
    workout: Workout
    /** The movements left out because they are no longer offered, each once. */
    skippedMovements: string[]
}

/** A row of the history. */
export interface WorkoutSummary {
    id: number
    name: string | null
    feeling: number | null
    startedAt: string
    finishedAt: string | null
    /** In the order they were done, each once. */
    movementNames: string[]
    setCount: number
    /** Null when no set measures it, as on the bilan. */
    volumeInKilograms: number | null
    durationInSeconds: number | null
    distanceInMetres: number | null
    /** The personal bests it beat, by one of its sets or as a whole. */
    personalBestCount: number
}

export interface Page<TItem> {
    items: TItem[]
    total: number
    page: number
    perPage: number
}

/** What a movement gave the last time it was done, before the workout asked about. */
export interface WorkoutPreviousPerformance {
    movementId: number
    workoutId: number
    startedAt: string
    sets: WorkoutSet[]
}

/** How much of a workout fell on one muscle. */
export interface WorkoutMuscleShare {
    muscleId: number
    muscleName: string
    /** Sets counted for it: 1 where it is the primary muscle, 0.5 where it is a secondary one. */
    setShare: number
    /** Out of every muscle's count, 0 to 100, to one decimal. */
    percentage: number
}

/** What a workout amounts to. A total is null when no set measures it, which is not zero. */
export interface WorkoutStats {
    workoutId: number
    setCount: number
    /** Reps times load; a unilateral set counts both sides. */
    volumeInKilograms: number | null
    durationInSeconds: number | null
    distanceInMetres: number | null
    /** The most worked first. */
    muscles: WorkoutMuscleShare[]
    /** The personal bests this workout beat, in the order records are listed in. */
    personalBests: PersonalBest[]
}

/** Every measure, null for those the movement does not track. Replaces a set whole. */
export interface WorkoutSetPayload {
    reps: number | null
    weightInKilograms: number | null
    durationInSeconds: number | null
    distanceInMetres: number | null
    rpe: number | null
    setTypeId: number | null
}

export interface WorkoutDetailsPayload {
    name: string | null
    note: string | null
    feeling: number | null
}

/** A routine as its list shows it. */
export interface RoutineSummary {
    id: number
    name: string
    /** A movement that comes twice counts twice. */
    movementCount: number
}

/** One set as a routine prescribes it: reps, and a share of the estimated 1RM for a loaded movement. */
export interface RoutineSet {
    id: number
    reps: number | null
    /** 1 to 100, by halves. Null for a movement without a load. */
    percentageOfOneRepMax: number | null
    /**
     * The load a workout started now would give it. Null without a share, or without an
     * estimated 1RM yet.
     */
    estimatedLoadInKilograms: number | null
}

export interface RoutineExercise {
    id: number
    movement: WorkoutMovement
    note: string | null
    restInSeconds: number | null
    sets: RoutineSet[]
}

export interface RoutineBlock {
    id: number
    exercises: RoutineExercise[]
}

/** The blueprint of a workout, whole. */
export interface Routine {
    id: number
    name: string
    blocks: RoutineBlock[]
}

/** Replaces a prescribed set whole. */
export interface RoutineSetPayload {
    reps: number | null
    percentageOfOneRepMax: number | null
}

/** When a planning entry falls: only the fields its rule reads are filled. */
export interface PlanningSchedule {
    /** once, weekly, monthly_weekday or monthly_day. */
    rule: string
    /** A calendar day, "YYYY-MM-DD". */
    startDate: string
    endDate: string | null
    /** ISO weekdays, 1 for Monday. */
    weekdays: number[]
    weekInterval: number
    /** 1 to 5, or -1 for the last. */
    monthWeeks: number[]
    monthDays: number[]
    /** "HH:MM", or null within the day. */
    startTime: string | null
    durationInMinutes: number | null
}

/** A routine planned on a rule of dates. */
export interface TrainingPlanningEntry {
    id: number
    routineId: number
    routineName: string
    schedule: PlanningSchedule
}

/** One date of a training entry. */
export interface TrainingPlanningSlot {
    entryId: number
    /** "YYYY-MM-DD". */
    day: string
    /** to_come, kept or missed. */
    state: string
    workoutId: number | null
}

export interface TrainingPlanning {
    entries: TrainingPlanningEntry[]
    /** By day, then by start time. */
    slots: TrainingPlanningSlot[]
}

export interface TrainingPlanningEntryPayload {
    routineId: number
    schedule: PlanningSchedule
}

/** The days a subscribed habit counts, on a rule of dates. */
export interface HabitPlanningEntry {
    id: number
    habitId: number
    habitName: string
    habitIcon: string
    schedule: PlanningSchedule
}

/** One date of a habit entry — or a day the habit was kept unplanned, with no entry. */
export interface HabitPlanningSlot {
    entryId: number | null
    habitId: number
    /** "YYYY-MM-DD". */
    day: string
    /** to_come, kept, missed or kept_unplanned. */
    state: string
}

export interface HabitPlanning {
    entries: HabitPlanningEntry[]
    /** By day, the planned dates by start time, then the days kept unplanned. */
    slots: HabitPlanningSlot[]
}

export interface HabitPlanningEntryPayload {
    habitId: number
    schedule: PlanningSchedule
}

/** A task model planned on a rule of dates: each date makes its own task. */
export interface TaskPlanningEntry {
    id: number
    title: string
    description: string | null
    category: Category | null
    schedule: PlanningSchedule
}

/** One date of a task entry. */
export interface TaskPlanningSlot {
    entryId: number
    /** The task made for that date; null while it is to come. */
    taskId: number | null
    /** "YYYY-MM-DD". */
    day: string
    /** to_come, open or done — an open task of a day gone by is overdue. */
    state: string
}

export interface TaskPlanning {
    entries: TaskPlanningEntry[]
    /** By day, then by start time. */
    slots: TaskPlanningSlot[]
}

export interface TaskPlanningEntryPayload {
    title: string
    description: string | null
    categoryId: number | null
    schedule: PlanningSchedule
}

/** Where some experience stands on the level curve: a stat's, or the whole of it. */
export interface Progress {
    level: number
    /** All the experience gathered. */
    experience: number
    /** How much of it counts towards the next level. */
    experienceIntoLevel: number
    /** What going to the next level takes, in all. */
    experienceForNextLevel: number
}

export interface Stat {
    /** strength, endurance, agility, intelligence, spirit or willpower. */
    stat: string
    progress: Progress
}

/** A heading over three stats, with no level of its own. */
export interface StatGroup {
    /** physical or mental. */
    group: string
    stats: Stat[]
}

/** The Statut window: the global level, then each stat by group. */
export interface Status {
    progress: Progress
    /** Physical, then mental. */
    groups: StatGroup[]
}
