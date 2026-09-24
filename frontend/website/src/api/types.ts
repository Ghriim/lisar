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
