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

export interface Priority {
    id: number
    label: string
    colour: string
    weight: number
    isDefault: boolean
}

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
    priority: Priority | null
    category: Category | null
    tags: string[]
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
    priorityId?: number | null
    categoryId?: number | null
    tags?: string[]
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
    setType: SetType | null
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
}

export interface WorkoutExercise {
    id: number
    movement: WorkoutMovement
    note: string | null
    /** In the order they were logged. */
    sets: WorkoutSet[]
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
    blocks: WorkoutBlock[]
}

/** A workout started from a past one, and what it could not take over. */
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
